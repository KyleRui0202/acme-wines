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
                'rule_spec' => 'in:',
            ],
        ]

        /*
         * Allowed zipcode pattern.   
        */
        
        'zipcode' => [
            'pattern' => [
                'rule_title' => 'ValidZipcodePattern',
                'rule_spec' => '/\d{5}[\-\*]\d{4}?/',
            ],
            'sum' => [
                'rule_title' => 'ZipCodeSum'
                'rule_spec' => 'max:20',
            ],
        ],

        /*
         * Allowed age to order.   
        */
        'birthday' => [
            'max_birth_date' => [
                'rule_title' => 'MinimumAgeForOrdering',
                'rule_spec' => 'before:21 years ago',
            ],
        ],

        /*
         * Extra email rules.   
        */
        'email' => [
            'domain_restrcition_in_ny' => [
                'rule_title' => 'EmailDomainRestrictionInNY',
                'rule_spec' => [
                    'NY' => ['.net'],
                ],
            ],
        ],

        /*
         * Priority rules for order validation.   
        */
        'priority_rules' => [
            'has_some_fields_same_as_next_order' => [
                'rule_title' => 'SameStateAndZipCodeAsNextOrder',
                'rule_spec' => ['state', 'zipcode'],
            ],
        ],

];

