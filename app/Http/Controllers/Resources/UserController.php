<?php

declare(strict_types=1);

namespace App\Http\Controllers\Resources;

use Baijunyao\LaravelRestful\Traits\Destroy;
use Baijunyao\LaravelRestful\Traits\ForceDelete;
use Baijunyao\LaravelRestful\Traits\Index;
use Baijunyao\LaravelRestful\Traits\Restore;
use Baijunyao\LaravelRestful\Traits\Show;
use Baijunyao\LaravelRestful\Traits\Update;

class UserController extends Controller
{
    use Index, Show, Update, Destroy, Restore, ForceDelete;
}
