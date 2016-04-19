<?php

namespace App\Jobs;

use App\Order;

class OrderFilterJob extends SyncJob
{

    /**
     * The eloquent or query builder instance.
     *
     * @var \Illuminate\Database\Eloquent\Builder
     */
    protected $query;

    /**
     * Allowed filters corresponding to the local scopes of Order
     *
     * @var string[]
     */
    protected $allowedFilters = [
        'constraint' => ['valid', 'limit', 'offset'],
        'field_match' => ['name', 'email', 'state', 'zipcode'],
        'field_partial_match' => ['name', 'email', 'zipcode']
    ];     

    /**
     * The parsed filter parameters.
     *
     * @var array
     */
    protected $parsedFilterParams;

    /**
     * Create a new job instance.
     *
     * @param array $filterParams
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return void
     */
    public function __construct($filterParams, $query = null)
    {
        $this->query = $query;
        $this->parsedFilterParams = [];
        foreach ($this->allowedFilters as $filterType => $subFilters) {
            $this->parseFilters($filterParams, $filterType, $subFilters);
        }
    }

    /**
     * Parse the filters of each type.
     *
     * @param array $filetrParams
     * @param string $filterType
     * @param array $filters
     * @return void
     */
    protected function parseFilters($filterParams, $filterType, $filters) {
        switch ($filterType) {
            case 'constraint':
                foreach ($filters as $filter) {
                    if (array_key_exists($filter, $filterParams)) {
                        $this->parseConstraintFilter($filter,
                            $filterParams[$filter]);
                    }
                }

                break;

            case 'field_partial_match':
                foreach ($filters as $filter) {
                    if (array_key_exists($filter.'_partial_match',
                        $filterParams)) {
                        $this->parseFieldPartialMatchFilter($filter,
                            $filterParams[$filter.'_partial_match']);
                    }
                }

                break;

            case 'field_match':
                foreach ($filters as $filter) {
                    if (array_key_exists($filter, $filterParams)) {
                        $this->parseFieldMatchFilter($filter,
                            $filterParams[$filter]);
                    }
                }

                break;
        }
    }

    /**
     * Parse a specific filter of the "constraint" type
     * inclding "valid", "limit" and "offset".
     *
     * @param string $filter
     * @param string $value
     */
    protected function parseConstraintFilter($filter, $value)
    {
        switch ($filter) {
            case 'valid':
                // Return TRUE for "1", "true", "on" and "yes";
                // return FALSE for "0", "false", "off", "no" and "";
                // Otherwise return NULL
                $isValidBool = filter_var($value, FILTER_VALIDATE_BOOLEAN,
                    FILTER_NULL_ON_FAILURE);

                if ($isValidBool !== null) {
                    $this->parsedFilterParams['constraint'][$filter] =
                        $isValidBool;
                }

                break;

            case 'limit':
            case 'offset':
                $parsedInt = filter_var($value, FILTER_VALIDATE_INT);

                if ($parsedInt !== false && $parsedInt > 0) {
                    $this->parsedFilterParams['constraint'][$filter] =
                        $parsedInt;
                }

                break;
        }
    }

    /**
     * Parse a specific filter of the "field_partial_match" type.
     *
     * @param string $filter
     * @param string $value
     */
    protected function parseFieldPartialMatchFilter($filter, $value)
    {
        $this->parsedFilterParams['field_partial_match'][$filter] = $value;
    }

    /**
     * Parse a specific filter of the "field_match" type.
     *
     * @param string $filter
     * @param string $value
     */
    protected function parseFieldMatchFilter($filter, $value)
    {
        $this->parsedFilterParams['field_match'][$filter] = $value;
    }

    /**
     * Execute the job.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function handle()
    {   
        $query = $this->query;
        
        foreach ($this->parsedFilterParams as $filterType => $filterParams) {
            switch ($filterType) {
                case 'constraint':
                    foreach ($filterParams as $key => $value) {
                        $query = $query ? $query->$key($value) :
                            Order::$key($value);
                    }

                    // If there is the "offset" filter but no "limit" filter,
                    // we will  create a one behind to include all the rest records
                    if (array_key_exists('offset', $filterParams) &&
                        !array_key_exists('limit', $filterParams)) {
                        $query = $query->take(Order::count());
                    }
                    break;
                case 'field_match':
                case 'field_partial_match':
                    $scopeFunc = camel_case($filterType);
                    foreach ($filterParams as $key => $value) {
                        $query = $query ? $query->$scopeFunc($key, $value) :
                            Order::$scopeFunc($key, $value);
                }
            }
        }
        return $query ? $query->get() : Order::all();
    }

    /**
     * Get the parsed filter parameters.
     *
     * @return array
     */
    public function getParsedFilterParams () {
        return $this->parsedFilterParams;
    }

}
