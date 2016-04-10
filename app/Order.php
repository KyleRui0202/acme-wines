<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Validator;

class Order extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'name', 'email', 'state', 'zipcode', 'birthday',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'created_at', 'updated_at', 'birthday',
    ]; 

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'valid' => 'boolean',
        'validation_errors' => 'array',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'created_at', 'updated_at',
    ]; 

    /**
     * Scope a query to only inlcude valid orders.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeValid($query, $boolValue)
    {
        return $query->where('valid', $boolValue);
    }

    /**
     * Scope a query to limit the number of returned orders.
     *
     * @param int $num
     * @return \Illuminate\Database\Eloquent\Builder
     */ 
    public function scopeLimit($query, $num)
    {
        return $query->take($num);
    }

    /**
     * Scope a query to skip a given number of orders.
     *
     * @param int $num
     * @return \Illuminate\Database\Eloquent\Builder
     */ 
    public function scopeOffset($query, $num)
    {
        return $query->skip($num);
    }

    /**
     * Scope a query to case-insensitively match a specific field of orders.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */ 
    public function scopeFieldMatch($query, $fieldName, $fieldValue)
    {
        return $query->whereRaw("lower(".$fieldName.
            ") ='".strtolower($fieldValue)."'");
    }
 
    /**
     * Scope a query to partially and case-insensitively
     * match a specific field of orders.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFieldPartialMatch($query, $fieldName, $fieldValue)
    {
        return $query->whereRaw("lower(".$fieldName.
            ") like '%".strtolower($fieldValue)."%'");
    }

    /*
     * Accessor for the column "birthday"
     *
     * @return string
     */
    public function getBirthdayAttribute()
    {
        return date_create($this->attributes['birthday'])
            ->format(config('ordercsv.birthday_format'));
    }

    /*
     * Mutator for the column "name"
     *
     * @param string $value
     * @return void
     */
    public function setNameAttribute($value)
    {
        $this->attributes['name'] = $value;
        $isValid = $this->validateRequiredAttribute('name', $value);
        if ($isValid === false) {
            $this->attributes['valid'] = false;
        }
    }

    /*
     * Mutator for the column "email"
     *
     * @param string $value
     * @return void
     */
    public function setEmailAttribute($value)
    {
        $parsedEmail = strtolower($value);
        $this->attributes['email'] = $parsedEmail;
        $isValid = $this->validateRequiredAttribute('email', $parsedEmail);
        if ($isValid === false) {
            $this->attributes['valid'] = false;
        }
    }

    /*
     * Mutator for the column "state"
     *
     * @param string $value
     * @return void
     */
    public function setStateAttribute($value)
    {
        $parsedState = strtoupper($value);
        $this->attributes['state'] = $parsedState;
        $isValid = $this->validateRequiredAttribute('state', $parsedState);
        if ($isValid === false) {
            $this->attributes['valid'] = false;
        }
    }

    /*
     * Mutator for the column "zipcode"
     *
     * @param string $value
     * @return void
     */
    public function setZipcodeAttribute($value)
    {
        $parsedZipcode = str_replace('*', '-', $value);
        $this->attributes['zipcode'] = $parsedZipcode;
        $isValid = $this->validateRequiredAttribute('zipcode', $parsedZipcode);
     
        if ($isValid === true) {
            $digitSumOfZipcode = array_sum(str_split(
                str_replace('-', '0', $parsedZipcode)));
            $isValid = $this->validateRequiredAttribute('digit_sum_of_zipcode',
                $digitSumOfZipcode);
        }
        if ($isValid === false) {
            $this->attributes['valid'] = false;
        }

    }

    /*
     * Mutator for the column "birthday"
     *
     * @param string $value
     * @return void
     */
    public function setBirthdayAttribute($value)
    {
        $this->attributes['birthday'] = $this->fromDateTime(
            date_create($value));
        $isValid = $this->validateRequiredAttribute('birthday', $value);
        if ($isValid === false) {
            $this->attributes['valid'] = false;
        }
    }

    /*
     * Validate a specific required field of orders.
     *
     * @param string $field
     * @param mixed $value
     * @return boolean
     */
    protected function validateRequiredAttribute($attribute, $value)
    {
        $validationConfig = 'validation.';
        $isAttributeValid = true;
        if (empty($value)) {
            $isAttributeValid = false;
            
            // Add a validation error for a required field
            $this->addValidationError('Required'.$ucfirst($attribute),
                'The '.$attribute.' is missing');
        }
        else if ($isAttributeValid === true){
            $validatorData = [];
            $validatorRules = [];
            $validationRules = config($validationConfig.$attribute) ?: [];
            foreach ($validationRules as $ruleName => $rule) {
                if (is_string($rule['rule_spec'])) {
                    $validatorData[$attribute.'.'.$ruleName] = $value;
                    $validatorRules[$attribute.'.'.$ruleName] = $rule['rule_spec'];
                }
                else {
                    if ($attribute === 'email' &&
                        $ruleName === 'domain_restriction_for_state') {
                        if (array_key_exists($this->attributes['state'],
                            $rule['rule_spec'])) {
                            foreach ($rule['rule_spec'][
                                $this->attributes['state']] as $domain) {
                                if (ends_with($value, $domain)) {
                                     $isAttributeValid = false;
                                     
                                     // Add a validation error for email domain
                                     // restriction for a specific state
                                     $this->addValidationError($rule['rule_title'],
                                         "The '".$domain."' email is not allowed in ".
                                         $this->attributes['state']);
                                }
                            }
                        } 
                    }
                }
            }
            if (count($validatorData) > 0) {
                $validator = Validator::make($validatorData, $validatorRules);
                if ($validator->fails()) {
                    $isAttributeValid = false;

                    // Attach each validation error detected by
                    // the "Validator" to the order model
                    foreach($validator->failed() as $ruleIndex => $ruleSpec) {
                        $this->addValidationError(config($validationConfig.
                            $ruleIndex.'.rule_title'), config($validationConfig.
                            $ruleIndex.'.error_message'));
                    }
                }
            }
        }
        return $isAttributeValid;
    }

    /*
     * Attch a validation error to the order model.
     *
     * @param string $rule
     * @param string $message
     * @return void
     */
    protected function addValidationError($rule, $message)
    {
        if (!array_key_exists('validation_errors',
            $this->attributes) && $this->exists) {

            // Reload the existing model to load its existing validation errors 
            $reloadedOrder = $this->fresh();
            $this->attributes['validation_errors'] =
                $reloadedOrder->validation_errors;
        }
        
        $validationErrors = array_key_exists('validation_errors',
            $this->attributes) && is_array($this->attributes[
            'validation_errors']) ? $this->fromJson($this->attributes[
            'validation_errors']) : [];
        array_push($validationErrors, [
            'rule' => $rule,
            'message' => $message]);        
        $this->attributes['validation_errors'] = $this->asJson($validationErrors);
    }

}
