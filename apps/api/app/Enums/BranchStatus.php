<?php

namespace App\Enums;

enum BranchStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Closed = 'closed';
}
