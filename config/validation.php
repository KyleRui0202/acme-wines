<?php

return [
        /*
         * Return configuration for order validation rules.   
        */

        
        /*
         * Allowed states to ship wines.  
         */

        'state' => [
            'allowed_states' => [
                'rule_title' => 'AllowedStates',
                'rule_spec' => 'in:AL,AK,AS,AZ,AR,CA,CO,DE,DC,FM,FL,GA,GU,HI,IN,IA,KS,KY,LA,ME,MH,MD,MI,MN,MS,MO,MT,NE,NV,NH,NM,NY,NC,ND,MP,OH,OK,PW,PR,RI,SC,SD,TN,TX,UT,VT,VI,VA,WA,WV,WI,WY',
                'error_message' => 'We can not ship to your state',
            ],
        ],

        /*
         * Zipcode validation rules.   
         */
        
        'zipcode' => [
            'pattern' => [
                'rule_title' => 'ValidZipcodePattern',
                'rule_spec' => 'regex:/^\d{5}([\-]\d{4})?$/',
                'error_message' => 'Your zipcode is not a valid 5-digit (e.g., 12345) or 9-digit (e.g., 12345-6789) zipcode',
            ],
        ],

        'digit_sum_of_zipcode' => [
            'range_limit' => [
                'rule_title' => 'ZipCodeSum',
                'rule_spec' => 'integer|max:20',
                'error_message' => 'Your zipcode sum is too large',
            ],
        ],

        /*
         * Bithday validation rules.   
         */

        'birthday' => [
            'type' => [
                'rule_title' => 'ValidBirthday',
                'rule_spec' => 'not_in:0000-00-00',
                'error_message' =>'Your birthday is a not valid date format required as "M j, Y", e.g, Jan 1, 1990',
            ],
            'max_birth_date' => [
                'rule_title' => 'MinimumAgeForOrdering',
                'rule_spec' => 'before:21 years ago',
                'error_message' => 'You must be 21 or older to order',
            ],
        ],

        /*
         * Email validation rules.   
         */

        'email' => [
            'type' => [
                'rule_title' => 'ValidEmailAddress',
                'rule_spec' => 'email',
                'error_message' => 'Your email address is not valid',
            ],
            'domain_restriction_for_state' => [
                'rule_title' => 'EmailDomainRestrictionForState',
                'rule_spec' => [
                    'NY' => ['.net'],
                ],
            ],
        ],

];

