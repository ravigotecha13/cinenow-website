<?php

namespace Modules\CastCrew\Services;

use Modules\CastCrew\Repositories\CastCrewRepositoryInterface;
use Modules\CastCrew\Support\CastCrewLocale;
use Illuminate\Support\Str;
use Yajra\DataTables\DataTables;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class CastCrewService
{
    protected $castcrewRepository;

    public function __construct( CastCrewRepositoryInterface $castcrewRepository)
    {
        $this->castcrewRepository = $castcrewRepository;
    }

    public function getAll()
    {
        return $this->castcrewRepository->all();
    }

    public function getById($id)
    {
        return $this->castcrewRepository->find($id);
    }

    public function create(array $data)
    {
        $cacheKey = 'castcrew_list';
        Cache::forget($cacheKey);
        return $this->castcrewRepository->create($data);
    }

    public function update($id, array $data)
    {
        $cacheKey = 'castcrew_list';
        Cache::forget($cacheKey);
        return $this->castcrewRepository->update($id, $data);
    }

    public function delete($id)
    {
        $cacheKey = 'castcrew_list';
        Cache::forget($cacheKey);
        return $this->castcrewRepository->delete($id);
    }

    public function restore($id)
    {
        $cacheKey = 'castcrew_list';
        Cache::forget($cacheKey);
        return $this->castcrewRepository->restore($id);
    }

    public function forceDelete($id)
    {
        $cacheKey = 'castcrew_list';
        Cache::forget($cacheKey);
        return $this->castcrewRepository->forceDelete($id);
    }

    public function getDataTable(Datatables $datatable, $filter, $type)
    {
        $query = $this->getFilteredData($filter ,$type);
        return $datatable->eloquent($query)
        ->addColumn('check', function ($row) {
            return '<input type="checkbox" class="form-check-input select-table-row"  id="datatable-row-'.$row->id.'"  name="datatable_ids[]" value="'.$row->id.'" data-type="cast-crew" onclick="dataTableRowCheck('.$row->id.', this)">';
        })
        ->editColumn('image', function ($data) {
            $type = 'castcrew';
            $imageUrl = setBaseUrlWithFileName($data->file_url);
            return view('components.media-item', [
                'thumbnail' => $imageUrl,
                'name' => CastCrewLocale::name($data),
                'designation' => CastCrewLocale::designation($data) ?? '',
                'type' => $type,
            ])->render();
        })

        ->editColumn('dob', function ($data) {

            $dob = $data->dob ? formatDate($data->dob) : '-';

           return  $dob ;
        })
        ->editColumn('place_of_birth', function ($data) {
            return CastCrewLocale::placeOfBirth($data) ?: '-';
        })

        ->filterColumn('dob', function($query, $keyword) {
            $query->whereRaw("DATE_FORMAT(dob, '%D %M %Y') like ?", ["%$keyword%"]);
        })



        ->addColumn('action', function ($data) {
            return view('castcrew::backend.castcrew.action', compact('data'));
        })



        ->editColumn('updated_at', function ($data) {


            $diff = Carbon::now()->diffInHours($data->updated_at);

            if ($diff < 25) {
                return $data->updated_at->diffForHumans();
            } else {
                return $data->updated_at->isoFormat('llll');
            }
         })
            ->orderColumns(['id'], '-:column $1')
            ->rawColumns(['action', 'check','image'])
            ->toJson();
    }

    public function getFilteredData($filter, $type)
    {
        $query = $this->castcrewRepository->query();

        if($type!=null){

            $query = $query->where('type',$type);
        }

        if(isset($filter)) {
            if (isset($filter['column_status'])) {
                $query->where('status', $filter['column_status']);
            }
        }

        if (isset($filter['type'])) {
            $query->where('type', $filter['type']);
        }

        if (isset($filter['name'])) {
            $term = $filter['name'];
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', '%' . $term . '%')
                    ->orWhere('name_en', 'like', '%' . $term . '%')
                    ->orWhere('name_ar', 'like', '%' . $term . '%');
            });
        }

        return $query;
    }

    public function getGenresList($perPage, $searchTerm = null)
    {
        return $this->castcrewRepository->list($perPage, $searchTerm);
    }


}
