<?php

namespace App\Http\Repositories;

use Illuminate\Database\Eloquent\Model;

class BaseRepository  implements BaseRepositoryInterface
{
    protected $model;


    public function __construct(Model $model)
    {
        $this->model = $model;
    }


    /**
     * @param array $attributes
     * @return mixed
     */
    public function create(array $attributes)
    {
        return $this->model->create($attributes);
    }

    /**
     * @param array $columns
     * @param string $orderBy
     * @param string $sortBy
     * @return mixed
     */
    public function all(array $columns = ['*'], string $orderBy = 'id', string $sortBy = 'desc', $with = [], $perPage  = 25)
    {
        return $this->model->filter()
            ->when($with, fn($q)  => $q->with($with))
            ->orderBy($orderBy, $sortBy)
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        // ->get($columns);
    }


    /**
     * @param array $data
     * @param Model|null $model
     * @return bool
     */
    public function update(array $data, Model $model = null): bool
    {
        if (!is_null($model)) {
            return $model->update($data);
        }
        return $this->model->update($data);
    }


    /**
     * @param string $id
     * @return mixed
     */
    public function find($id)
    {
        return $this->model->find($id);
    }

    /**
     * @param  $id
     * @return mixed
     * @throws ModelNotFoundException
     */
    public function findOneOrFail($id)
    {
        return $this->model->findOrFail($id);
    }


    /**
     * @param array $data
     * @return mixed
     */
    public function findBy(array $data, string $orderBy = 'id', string $sortBy = 'desc', $with = [], $perPage  = 25)
    {
        return $this->model->where($data)
            ->when($with, fn($q)  => $q->with($with))
            ->orderBy($orderBy, $sortBy)
            ->latest()
            // ->paginate($perPage);
            ->get();
    }

    /**
     * @param array $data
     * @return mixed
     */
    public function findOneBy(array $data, $with = [])
    {
        return $this->model->where($data)
            ->when($with, fn($q)  => $q->with($with))
            ->first();
    }

    /**
     * @param array $data
     * @return mixed
     * @throws ModelNotFoundException
     */
    public function findOneByOrFail(array $data)
    {
        return $this->model->where($data)->firstOrFail();
    }

    /**
     * @return bool
     * @throws \Exception
     */
    public function delete(): bool
    {
        return $this->model->delete();
    }
}