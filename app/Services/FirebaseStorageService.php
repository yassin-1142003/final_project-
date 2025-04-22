<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Kreait\Firebase\Contract\Storage as FirebaseStorage;

class FirebaseStorageService
{
    protected $storage;
    protected $bucket;

    public function __construct(FirebaseStorage $storage)
    {
        $this->storage = $storage;
        $this->bucket = $storage->getBucket();
    }

    public function uploadImage(UploadedFile $file, string $path): string
    {
        $name = time() . '_' . $file->getClientOriginalName();
        $fullPath = $path . '/' . $name;
        
        $object = $this->bucket->upload(
            $file->get(),
            ['name' => $fullPath]
        );

        return $object->signedUrl(new \DateTime('+ 1000 years'));
    }

    public function deleteImage(string $url): bool
    {
        try {
            $path = parse_url($url, PHP_URL_PATH);
            $object = $this->bucket->object(ltrim($path, '/'));
            $object->delete();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
} 