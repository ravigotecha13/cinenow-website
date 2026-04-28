<?php

namespace Modules\CastCrew\Repositories;

use Modules\CastCrew\Models\CastCrew;
use Auth;

class CastCrewRepository implements CastCrewRepositoryInterface
{
    public function all()
    {
        return CastCrew::all();
    }

    public function find($id)
    {
        $genreQuery = CastCrew::query();

        if (Auth::user()->hasRole('user')) {
            $genreQuery->whereNull('deleted_at'); // Only show non-trashed genres
        }

        $genre = $genreQuery->withTrashed()->findOrFail($id);
        $genre->file_url = setBaseUrlWithFileName($genre->file_url);
        return $genre;
    }

    public function create(array $data)
    {
        $data = $this->syncLegacyColumns($data);

        return CastCrew::create($data);
    }

    public function update($id, array $data)
    {
        $genre = CastCrew::findOrFail($id);
        $data = $this->syncLegacyColumns($data);
        $genre->update($data);
        return $genre;
    }

    /**
     * Keep legacy single-locale columns aligned with English fields (imports, API, exports).
     */
    protected function syncLegacyColumns(array $data): array
    {
        if (isset($data['name_en'])) {
            $data['name'] = $data['name_en'];
        }
        if (isset($data['bio_en'])) {
            $data['bio'] = $data['bio_en'];
        }
        if (isset($data['place_of_birth_en'])) {
            $data['place_of_birth'] = $data['place_of_birth_en'];
        }
        if (array_key_exists('designation_en', $data)) {
            $data['designation'] = $data['designation_en'] !== '' && $data['designation_en'] !== null
                ? $data['designation_en']
                : null;
        }

        return $data;
    }

    public function delete($id)
    {
        $genre = CastCrew::findOrFail($id);
        $genre->delete();
        return $genre;
    }

    public function restore($id)
    {
        $genre = CastCrew::withTrashed()->findOrFail($id);
        $genre->restore();
        return $genre;
    }

    public function forceDelete($id)
    {
        $genre = CastCrew::withTrashed()->findOrFail($id);
        $genre->forceDelete();
        return $genre;
    }

    public function query()
    {

        $genreQuery=CastCrew::query()->withTrashed();

        if(Auth::user()->hasRole('user') ) {
            $genreQuery->whereNull('deleted_at');
        }

        return $genreQuery;

    }

    public function list($perPage, $searchTerm = null)
    {
        $query = CastCrew::query();

        if ($searchTerm) {
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%")
                    ->orWhere('name_en', 'like', "%{$searchTerm}%")
                    ->orWhere('name_ar', 'like', "%{$searchTerm}%");
            });
        }

        $query->where('status', 1)
              ->orderBy('updated_at', 'desc');

        return $query->paginate($perPage);
    }


}
