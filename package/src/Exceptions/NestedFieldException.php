<?php

namespace Esoboy\Ui\Exceptions;

use LogicException;

class NestedFieldException extends LogicException
{
    public function __construct()
    {
        parent::__construct(
            '<x-ui::field> cannot contain another <x-ui::field>. Group fields with <x-ui::field.group> or <x-ui::field.set> instead.'
        );
    }
}
