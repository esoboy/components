<?php

namespace Esoboy\Ui\Appearance;

use Illuminate\Http\Request;

class Appearance
{
    public const COOKIE = 'ui_appearance';

    public const LIGHT = 'light';

    public const DARK = 'dark';

    public const SYSTEM = 'system';

    /**
     * The appearance the visitor picked, or "system" when they never chose one.
     */
    public static function fromRequest(Request $request): string
    {
        $value = $request->cookies->get(self::COOKIE);

        return in_array($value, [self::LIGHT, self::DARK], true) ? $value : self::SYSTEM;
    }
}
