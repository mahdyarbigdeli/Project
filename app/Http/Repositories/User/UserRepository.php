<?php

namespace App\Http\Repositories\User;

use App\Http\Repositories\BaseRepository;
use App\Http\Repositories\Company\Interfaces\CompanyRepositoryInterface;
use App\Http\Repositories\User\Interfaces\UserRepositoryInterface;
use App\Models\Company;
use App\Models\User;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Pagination\LengthAwarePaginator;

class UserRepository  extends BaseRepository implements UserRepositoryInterface
{
    protected $model;
    /**
     * ClubCardRepository constructor.
     * @param Company $company
     */
    public function __construct(User $model)
    {
        parent::__construct($model);
        $this->model = $model;
    }
}
