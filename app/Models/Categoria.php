<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @method static \Illuminate\Database\Eloquent\Builder query()
 * @method static \App\Models\Categoria create(array $attributes = [])
 * @method static \App\Models\Categoria|null find($id)
 * @property int $id
 * @property string $nombre
 * @property string|null $descripcion
 * @property bool $estado
 */
class Categoria extends Model
{
    use HasFactory;

    protected $table = 'categorias';

    protected $fillable = [
        'nombre',
        'descripcion',
        'estado',
    ];

    protected $casts = [
        'estado' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function scopeActivas($query)
    {
        return $query->where('estado', true);
    }

    public function scopeInactivas($query)
    {
        return $query->where('estado', false);
    }

    public function productos()
    {
        return $this->hasMany(Producto::class);
    }
}
