<?php

namespace Modules\Genres\Models;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Entertainment\Models\EntertainmentGenerMapping;

class Genres extends BaseModel
{
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     */
    protected $table = 'genres';

    protected $fillable = [
        'name',
        'name_en',
        'name_ar',
        'slug',
        'file_url',
        'description',
        'description_en',
        'description_ar',
        'status',
    ];


    public function setSlugAttribute($value)
    {
        $this->attributes['slug'] = slug_format(trim($value));

        if (empty($value)) {
            $fallback = $this->attributes['name_en'] ?? $this->attributes['name'] ?? '';
            $this->attributes['slug'] = slug_format(trim($fallback));
        }
    }


    public function entertainmentGenerMappings()
    {
        return $this->hasMany(EntertainmentGenerMapping::class,'genre_id','id');
    }

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($genre) {
            if (empty($genre->name_en) && ! empty($genre->name)) {
                $genre->name_en = $genre->name;
            }
            if (empty($genre->description_en) && ! empty($genre->description)) {
                $genre->description_en = $genre->description;
            }
        });

        static::deleting(function ($genre) {

            if ($genre->isForceDeleting()) {

                $genre->entertainmentGenerMappings()->forcedelete();

            } else {
                $genre->entertainmentGenerMappings()->delete();
             }

        });

        static::restoring(function ($genre) {

            $genre->entertainmentGenerMappings()->withTrashed()->restore();
            
        });
    }

    
}