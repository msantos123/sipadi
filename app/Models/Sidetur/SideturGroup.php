<?php

namespace App\Models\Sidetur;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SideturGroup extends Model
{
    use HasFactory;

    /**
     * The connection name for the model.
     *
     * @var string|null
     */
    protected $connection = 'mysql_sidetur';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'groups';
}
