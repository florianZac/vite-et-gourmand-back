<?php

namespace App\Service;

use Cloudinary\Cloudinary;

/** 
 * @author      Florian Aizac
 * @created     26/03/2026
 * @description Service gérant les données pour l'accès APi pour la gestion des images
 *  1. upload()              : Upload une image vers Cloudinary
 *  2. deleteByUrl()         : Supprime une image de Cloudinary à partir de son URL
 *  3. extractPublicId()     : Extrait le public_id d'une URL Cloudinary
 *
*/
class CloudinaryService
{
    private Cloudinary $cloudinary;

    public function __construct(string $cloudinaryUrl)
    {
        $this->cloudinary = new Cloudinary($cloudinaryUrl);
    }

    /**
     * @description Upload une image vers Cloudinary
     * @param string $filePath Chemin local du fichier temporaire
     * @param string $folder Dossier de destination sur Cloudinary
     * @return string L'URL publique de l'image uploadée
     */
    public function upload(string $filePath, string $folder = 'vite-et-gourmand/plats'): string
    {
        $result = $this->cloudinary->uploadApi()->upload($filePath, [
            'folder' => $folder,
            'resource_type' => 'image',
            'allowed_formats' => ['jpg', 'jpeg', 'png', 'webp'],
        ]);

        return $result['secure_url'];
    }

    /**
     * @description Supprime une image de Cloudinary à partir de son URL
     * @param string $url L'URL complète de l'image Cloudinary
     * @return bool true si supprimée, false sinon
     */
    public function deleteByUrl(string $url): bool
    {
        // Extraire le public_id depuis l'URL Cloudinary
        // URL type : https://res.cloudinary.com/CLOUD/image/upload/v123/vite-et-gourmand/plats/abc123.jpg
        $publicId = $this->extractPublicId($url);
        
        if (!$publicId) {
            return false;
        }

        try {
            $result = $this->cloudinary->uploadApi()->destroy($publicId);
            return ($result['result'] === 'ok');
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * @description Extrait le public_id d'une URL Cloudinary
     * Ex: https://res.cloudinary.com/xxx/image/upload/v123/vite-et-gourmand/plats/abc.jpg
     * vite-et-gourmand/plats/abc
     */
    private function extractPublicId(string $url): ?string
    {
        // Vérifie que c'est bien une URL Cloudinary
        if (strpos($url, 'cloudinary.com') === false) {
            return null;
        }

        // Découpe l'URL après /upload/
        $parts = explode('/upload/', $url);
        if (count($parts) < 2) {
            return null;
        }

        // Retire le versioning (v123456789/)
        $path = preg_replace('/^v\d+\//', '', $parts[1]);

        // Retire l'extension du fichier
        $publicId = preg_replace('/\.[^.]+$/', '', $path);

        return $publicId;
    }
}