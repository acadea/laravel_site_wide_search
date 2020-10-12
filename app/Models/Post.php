<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;

class Post extends Model
{
    use HasFactory;

    use Searchable;

    public function toSearchableArray()
    {
        return $this->only(['id', 'name']);
    }
}
