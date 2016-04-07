<?php

namespace App\Jobs;

use App\Order;

class OrderFilterJob extends Job
{
    /**
     * The query builder instance.
     *
     * @var \Illuminate\Database\Query\Builder
     */
    protected $query;

    /**
     * Allowed filters corresponding to the local scopes of Order
     *
     * @var string[]
     */
    protected allowedFilters = ['valid', 'limit', 'offset', 'field:name',
        'field:email', 'field:state', 'field:zipcode', 'field:birthday'];     

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
     * @param \Illuminate\Database\Query\Builder $query
     * @return void
     */
    public function __construct($filterParams, $query = null)
    {
        foreach ($this->allowedFilters as $filter) {
            if (starts_with($filter, 'field:')) {
                $parsedFilter = substr($filter, 6);
            	if (array_key_exists($parsedFilter, $filterParams)) {
                    $this->parsedFilterParams['field'][$parsedFilter] =
                        $filterParams[$parsedFilter];
                }
            }
            else if (array_key_exists($filter, $filterParams)) {
                $this->parsedFilterParams[$filter] =
                    $filterParams[$filter];
            } 
        }

        $this->query = $query;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        foreach ($this->parsedFilterParams as $filterType => $filterValue) {
            if ($filerType == 'field') {
                foreach ($filterValue as $key => $value) {
                    if (is_null($this->query)) {
                        $this->query = Order::field($key, $value);
                    }
                    else {
                        $this->query = $this->query->field($key, $value)
                    }
                }
            }
            else if (is_null($this->query)) {
                $this->query = Order::$filterType($filterValue);
            }
            else {
                $this->query = $this->query->$filterType($filterValue);
            }
        }
        return is_null($this->query) ? Order::all() : $this->query->get();
    }
}
