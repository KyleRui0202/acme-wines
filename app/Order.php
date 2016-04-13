<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Validator;

class Order extends Model
{
    /*
     * The primary key is not auto-incrementing  
     *
     * @var boolean
     */
    public $incrementing = false;

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
        'id' => 'integer',
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
        return $this->asDateTime($this->attributes['birthday'])
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
        $parsedName = trim($value);
        $this->attributes['name'] = $parsedName;
        $isValid = $this->validateRequiredAttribute('name', $parsedName);
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
        $parsedEmail = strtolower(trim($value));
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
        $parsedState = strtoupper(trim($value));
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
        $parsedZipcode = str_replace('*', '-', trim($value));
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
        $parsedBirthday = trim($value);
        if (!empty($parsedBirthday)) {
            $parsedBirthday = date_create_from_format(
                config('ordercsv.birthday_format'), $parsedBirthday);
            $parsedBirthday = $parsedBirthday ?
                $parsedBirthday->format('Y-m-d') : '0000-00-00';
        }
        $this->attributes['birthday'] = $parsedBirthday;
   
        $isValid = $this->validateRequiredAttribute('birthday',
            $parsedBirthday);
        if ($isValid === false) {
            $this->attributes['valid'] = false;
        }
    }

    /*
     * Validate a specific required field of orders.
     * Add and update a specific validation error if find any.
     * Otherwise we may need to remove an error (which is not implemented here)
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
            $this->addOrUpdateValidationError('Required'.ucfirst($attribute),
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
                if ($ruleName === 'domain_restriction_for_stat') {
                    $curState = $this->getAttributeForSameOrder('state');
                    //dd($curState);
                    if (!is_null($curState)) {
                        $isAttributeValid = $this->
                            detectEmailDomainRestrictionForState(
                                $value, $curState, $rule);
                    }
                }
                else if ($attribute === 'state' && !empty(config(
                    $validationConfig.'email.domain_restriction_for_state'))) {
                    $curEmail = $this->getAttributeForSameOrder('email');
                    //dd($curEmail);
                    if (!is_null($curEmail)) {
                        $isAttributeValid = $this->
                            detectEmailDomainRestrictionForState(
                                $curEmail, $value, config($validationConfig.
                                'email.domain_restriction_for_state'));
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
                        $this->addOrUpdateValidationError(config($validationConfig.
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
    protected function addOrUpdateValidationError($ruleTitle, $message)
    {
        $validationErrors = $this->getAttributeForSameOrder(
            'validation_errors') ?: [];
        if (count($validationErrors) > 0) {
            //dd([$validationErrors, $ruleTitle]);
        }
        foreach ($validationErrors as $validationError) {
            if ($validationError['rule'] === $ruleTitle) {
                $validationError['message'] = $message;
                return;
            }
        }
        array_push($validationErrors, [
            'rule' => $ruleTitle,
            'message' => $message]);
        $this->validation_errors = $validationErrors;
    }

    /*
     * Get the value of an attribute corresponsind to
     * the same order record of the model instance
     *
     * @param string $attribute
     * @return mixed|null
     */
    protected function getAttributeForSameOrder($attribute)
    {
        if (isset($this->$attribute)) {
            return $this->$attribute;
        }
        else if ($this->exists) {

            // Reload the existing model to load
            // its existing validation errors 
            $reloadedOrder = $this->fresh();
            return $reloadedOrder->$attribute;
        }
        return null;
    }

    /*
     * Check if a email value with a state value break
     * "email_domain_restriction_for_state_rule"
     *
     * @param string $emailValue
     * @param string $stateValue
     * @param string $rule
     * @return boolean
     */
    protected function detectEmailDomainRestrictionForState($emailValue, $stateValue, $rule)
    {
        $isValid = true;
        if (array_key_exists($stateValue, $rule['rule_spec'])) {
            foreach ($rule['rule_spec'][$stateValue] as $domain) {
                if (ends_with($emailValue, $domain)) {
                    $isValid = false;

                    // Add a validation error for email domain
                    // restriction for a specific state
                    $this->addOrUpdateValidationError($rule['rule_title'],
                        "The '".$domain."' email is not allowed in ".$stateValue);
                    break;
                }
            }
        }
        return $isValid;
    }

}
