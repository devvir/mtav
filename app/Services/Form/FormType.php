<?php

namespace App\Services\Form;

enum FormType: string
{
    case CREATE = 'create';
    case UPDATE = 'update';
}
