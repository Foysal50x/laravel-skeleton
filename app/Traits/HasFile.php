<?php


namespace App\Traits;


use Closure;
use Exception;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Http\FormRequest;
use JetBrains\PhpStorm\Pure;

trait HasFile
{
    public array $paths = [];

    private string $disk = 'public';

    public function upload(string $fileName, string $directory = 'upload', Request|FormRequest $request = null):self
    {
        $request ??= request();
        if ($request->hasFile($fileName)) {
            $this->paths = array_map(
                fn(UploadedFile $file) => Storage::disk($this->disk ??= 'public')->put($directory, $file),
                Arr::wrap($request->file($fileName))
            );
        }
        return $this;
    }

    public function disk(string $disk):self
    {
        if (empty(trim($disk))) {
            throw new Exception("Disk can't empty string");
        }
        $this->disk = $disk;
        return $this;
    }

    #[Pure]
    public function hasFile(): int
    {
        return count($this->paths);
    }

    public function filePaths(): Collection
    {
        return collect($this->paths);
    }

    public function firstPath():?string
    {
        return $this->filePaths()->first();
    }

    public function delete(string $filePath = null): bool|int
    {
        if(empty(trim($this->disk))) {
            throw new Exception("Please select disk first!");
        }
        if (!is_null($filePath)) {
            return Storage::disk($this->disk)->delete($filePath);
        }
        $count = 0;
        if($this->hasFile()) {
            $this->filePaths()->each(Closure::bind(function($path) use(&$count){
                if (Storage::disk($this->disk)->delete($path)) {
                    $count++;
                }
            }, $this));
        }
        return $count;
    }
}
