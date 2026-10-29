<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = ['key', 'value', 'type'];

    public static function get(string $key, mixed $default = null): mixed
    {
        $row = static::query()->where('key', $key)->first();
        if (!$row) return $default;
        return match ($row->type) {
            'json'  => json_decode($row->value, true),
            'bool'  => (bool) $row->value,
            'float', 'int' => $row->value + 0,
            default => $row->value,
        };
    }

    public static function set(string $key, mixed $value, string $type = 'string'): void
    {
        static::query()->updateOrCreate(
            ['key' => $key],
            ['value' => is_array($value) ? json_encode($value) : (string) $value, 'type' => $type],
        );
    }
}
