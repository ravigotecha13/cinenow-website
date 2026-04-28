<?php

namespace Modules\CastCrew\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use  Modules\Entertainment\Models\EntertainmentTalentMapping;

class CastCrew extends BaseModel
{
    use SoftDeletes;

     protected $table = 'cast_crew';

     protected $fillable = [
         'name',
         'name_en',
         'name_ar',
         'type',
         'file_url',
         'tmdb_id',
         'bio',
         'bio_en',
         'bio_ar',
         'place_of_birth',
         'place_of_birth_en',
         'place_of_birth_ar',
         'dob',
         'designation',
         'designation_en',
         'designation_ar',
     ];


     public function entertainmentTalentMappings()
     {
         return $this->hasMany(EntertainmentTalentMapping::class,'talent_id','id');
     }
 

     protected static function boot()
     {
         parent::boot();

         static::saving(function ($castcrew) {
             if (empty($castcrew->name_en) && ! empty($castcrew->name)) {
                 $castcrew->name_en = $castcrew->name;
             }
             if (empty($castcrew->bio_en) && ! empty($castcrew->bio)) {
                 $castcrew->bio_en = $castcrew->bio;
             }
             if (empty($castcrew->place_of_birth_en) && ! empty($castcrew->place_of_birth)) {
                 $castcrew->place_of_birth_en = $castcrew->place_of_birth;
             }
             if ($castcrew->designation_en === null && $castcrew->designation !== null && $castcrew->designation !== '') {
                 $castcrew->designation_en = $castcrew->designation;
             }
         });
 
         static::deleting(function ($castcrew) {
 
             if ($castcrew->isForceDeleting()) {
 
                 $castcrew->entertainmentTalentMappings()->forcedelete();
 
             } else {
                 $castcrew->entertainmentTalentMappings()->delete();
              }
 
         });
 
         static::restoring(function ($castcrew) {
 
             $castcrew->entertainmentTalentMappings()->withTrashed()->restore();
             
         });
     }
 
    
}
