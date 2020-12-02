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

trait HasFile
{
    /**
     * @var array $paths
     */
    public $paths = [];
    /**
     * @var string $disk
     */
    private $disk = 'public';

    /**
     * @param string $fileName
     * @param string $directory
     * @param Request|FormRequest|null $request
     * @return $this
     */
    public function upload(string $fileName, string $directory = 'upload', $request = null):self
    {
        $request = $request ?? request();
        if ($request->hasFile($fileName)) {
            $this->paths = array_map(Closure::bind(function(UploadedFile $file)use($directory) {
                return Storage::disk($this->disk = $this->disk ?? 'public')
                    ->put($directory, $file);
            }, $this), Arr::wrap($request->file($fileName)));
        }
        return $this;
    }

    /**
     * @param string $disk
     * @return $this
     * @throws Exception
     */
    public function disk(string $disk):self
    {
        if (empty(trim($disk))) {
            throw new Exception("Disk can't empty string");
        }
        $this->disk = $disk;
        return $this;
    }

    /**
     * @return int
     */
    public function hasFile(): int
    {
        return count($this->paths);
    }

    /**
     * @return Collection
     */
    public function filePaths(): Collection
    {
        return collect($this->paths);
    }

    /**
     * @return string|null
     */
    public function firstPath():?string
    {
        return $this->filePaths()->first();
    }

    /**
     * @param string|null $filePath
     * @return bool|int
     * @throws Exception
     */
    public function delete(string $filePath = null)
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
