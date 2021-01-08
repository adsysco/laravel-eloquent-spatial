<?php

namespace MatanYadaev\EloquentSpatial\Objects;

use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

abstract class GeometryCollection extends Geometry
{
    /** @var Collection<Geometry> */
    protected Collection $geometries;

    public int $srid;

    protected string $collectionOf = Geometry::class;

    protected int $minimumGeometries = 0;

    /**
     * @param Collection<Geometry>|Geometry[] $geometries
     * @param int|null $srid
     * @throws InvalidArgumentException
     */
    public function __construct(Collection | array $geometries, ?int $srid = 0)
    {
        parent::__construct($srid);

        if (is_array($geometries)) {
            $geometries = collect($geometries);
        }

        $this->geometries = $geometries;

        $this->validateGeometriesCount();
        $this->validateGeometriesType();
    }

    /**
     * @throws InvalidArgumentException
     */
    protected function validateGeometriesCount(): void
    {
        $geometriesCount = $this->geometries->count();
        if ($geometriesCount < $this->minimumGeometries) {
            $className = get_class($this);

            throw new InvalidArgumentException("{$className} must contain at least {$this->minimumGeometries} ".Str::plural('entries', $geometriesCount));
        }
    }

    /**
     * @throws InvalidArgumentException
     */
    protected function validateGeometriesType(): void
    {
        $this->geometries->each(function (Geometry $geometry): void {
            if (! ($geometry instanceof $this->collectionOf)) {
                $className = get_class($this);

                throw new InvalidArgumentException("{$className} must be a collection of {$this->collectionOf}");
            }
        });
    }

    abstract public function toWkt(): Expression;

    protected function toCollectionWkt(): Expression
    {
        $wkb = $this->geometries
            ->map(static function (Geometry $geometry):string {
                return $geometry->toWkt();
            })
            ->join(',');

        return DB::raw($wkb);
    }

    /**
     * @return Geometry[]
     */
    public function getGeometries(): array
    {
        return $this->geometries->all();
    }
}