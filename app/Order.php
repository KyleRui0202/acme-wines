<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'name', 'email', 'state', 'zipcode', 'birthday'
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'created_at', 'updated_at', 'birthday'
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
     * Scope a query to only inlcude valid orders.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeValid($query) {
        return $query->where('valid', true);
    }

    /**
     * Scope a query to limit the number of returned orders.
     *
     * @param int $num
     * @return \Illuminate\Database\Eloquent\Builder
     */ 
    public function scopeLimit($query, $num) {
        return $query->take($num);
    }

    /**
     * Scope a query to skip a given number of orders.
     *
     * @param int $num
     * @return \Illuminate\Database\Eloquent\Builder
     */ 
    public function scopeOffset($query, $num) {
        return $query->skip($num);
    }

    /**
     * Scope a query to match a specific field of orders.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */ 
    public function scopeFieldMatch($query, $fieldName, $fieldValue) {
        return $query->where($fieldName, $fieldValue);
    }
 
    /**
     * Scope a query to partially match a specific field of orders.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFieldPartialMatch($query, $fieldName, $fieldValue) {
        return $query->where($fieldName, 'like', '%'.$fieldValue.'%');
    }

    /*
     * Accessor for the column "birthday"
     *
     * @return string
     */
    public function getBirthdayAttribute() {
        return $this->birthday->format(config('ordercsv.birthday_format'));
    }


    /*
     * Mutator for the column "name"
     *
     * @param string $value
     * @return void
     */
    public function setNameAttribute($value) {
        $this->attributes['name'] = $value;
        $isValid = $this->validateRequiredAttribute('name', $value);
        if ($isValid === false && $this->attributes['valid'] == true) {
            $this->attributes['valid'] = false;
        }
    }

    /*
     * Mutator for the column "email"
     *
     * @param string $value
     * @return void
     */
    public function setEmailAttribute($value) {
        $this->attributes['email'] = $value;
        $isValid = $this->validateRequiredAttribute('email', $value);
        if ($isValid === false && $this->attributes['valid'] == true) {
            $this->attributes['valid'] = false;
        }
    }

    /*
     * Mutator for the column "state"
     *
     * @param string $value
     * @return void
     */
    public function setStateAttribute($value) {
        $this->attributes['state'] = $value;
        $isValid = $this->validateRequiredAttribute('state', $value);
        if ($isValid === false && $this->attributes['valid'] == true) {
            $this->attributes['valid'] = false;
        }
    }

    /*
     * Mutator for the column "zipcode"
     *
     * @param string $value
     * @return void
     */
    public function setZipcodeAttribute($value) {
        $parsedZipcode = str_replace('*', '-', $value);
        $this->attributes['zipcode'] = $parsedZipcode;
        $isValid = $this->validateRequiredAttribute('zipcode', $parsedZipcode);
     
        if ($isValid === false) {
            $digitSumOfZipcode = array_sum(str_split(
                str_replace('-', '0', $parsedZipcode)));
            $isValid = $this->validateRequiredAttribute('digit_sum_of_zipcode',
                $digitSumOfZipcode);
        }
        if ($isValid === false && $this->attributes['valid'] == true) {
            $this->attributes['valid'] = false;
        }

    }

    /*
     * Mutator for the column "birthday"
     *
     * @param string $value
     * @return void
     */
    public function setBirthdayAttribute($value) {
        $this->birthday = $value;
        $isValid = $this->validateRequiredAttribute('birthdaty', $value);
        if ($isValid === false && $this->attributes['valid'] == true) {
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
    protected function validateRequiredAttribute($attribute, $value) {
        $vaidationConfig = 'validation.';
        $isAttributeValid = true;
        if (empty($value)) {
            $isAttributeValid = false;
            
            // Add a validation error for a required field
            $this->addValidationError('Required'.$ucfirst($field),
                'The '.$attribute.' is missing');
        }
        else if ($isAttributeValid === true){
            $validatorData = [];
            $validatorRules = []
            foreach (config($vaidationConfig.$attribute) as $ruleName => $rule) {
                if (is_string($rule['rule_spec'])) {
                    $validatorData[$attribute.'.'.$ruleName] = $value;
                    $validatorRules[$attribute.'.'.$ruleName] = $rule['rule_spec'];
                }
                else {
                    if ($attribute === 'email' &&
                        $ruleName === 'domain_restriction_for_state') {
                        if (array_key_exists($this->attributes['state'],
                            $rule['rule_spe'])) {
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

                    // Add validation error for a specific attribute
                    foreach($validator->failed() as $ruleIndex => $ruleSpec) {
                        $this->addValidationError(config($vaidationConfig.
                            $ruleIndex.'.rule_title'), config($vaidationConfig.
                            $ruleIndex.'.error_message')),
                        );
                    }
                }
            }
        }
        return isAttributeValid;
    }

    /*
     * Add a validation error.
     *
     * @param string $rule
     * @param string $message
     * @return void
     */
    protected function addValidationError($rule, $message) {
        $existingErrors = $this->attributes['validation_errors'];
        $existingErrors[] = ['rule' => $rule, 'message' => $message];
        $this->attributes['validation_errors'] = $existingErrors;
    }

}
