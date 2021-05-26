<?php

declare(strict_types=1);

namespace Chiron\Publisher;

use ArrayIterator;
use Chiron\Container\SingletonInterface;
use Chiron\Filesystem\Filesystem;
use Chiron\Core\Exception\PublishException;
use Countable;
use IteratorAggregate;
use Transversable;

//https://github.com/top-think/framework/blob/6.0/src/think/console/command/VendorPublish.php#L35

// TODO : ajouter la gestion des tags.
//https://github.com/laravelista/lumen-vendor-publish/blob/master/src/VendorPublishCommand.php
//https://github.com/illuminate/support/blob/master/ServiceProvider.php#L370

// TODO : créer un package dédié qui serait nommé chiron/publisher et ajouter dans le fichier composer de core, une dépendance vers ce package chiron/publisher ???? (penser à virer le "implements SingletonInterface" pour éviter une dépendance vers le composant chiron/container)

// TODO : ajouter une méthode pour récupérer la liste des items à publier, un truc du genre getItems(): array   et cela retournera le tableau $this->publishes;
final class Publisher //implements SingletonInterface
{
    /**
     * The paths that should be published.
     *
     * @var array
     */
    private $publishes = [];
    /**
     * @var Filesystem
     */
    private $filesystem;
    /**
     * @var callable
     */
    private $callback;
    /**
     * @var bool
     */
    private $force; // TODO : renommer en $forceCopy ou $overwrite

    /**
     * Create a new command instance.
     *
     * @param Filesystem $files
     */
    public function __construct()
    {
        $this->filesystem = new Filesystem();
    }

    // TODO : renommer cette méthode en onCopy(callable $callback)  ???? cela sera plus simple ca reprend le nommage des "événements"
    // TODO : utiliser plutot une propriété public de classe $onCopy qui serait un callable et qu'on appellerait à chaque copie de fichier !!!!
    public function setCallback(callable $callback)
    {
        $this->callback = $callback;
    }

    // TODO : renommer en addItem() ????
    // TODO : ajouter un 3eme paramétre pour passer une string $tag
    public function add(string $source, string $destination)
    {
        // Normalization is applied to have more "understandable" path if there are displayed later.
        $source = $this->filesystem->normalizePath($source);
        $destination = $this->filesystem->normalizePath($destination);

        $this->publishes[$source] = $destination;
    }

    // TODO : renommer en publishAll ou publishItems ???
    public function publish(bool $force): void
    {
        $this->force = $force;

        foreach ($this->publishes as $from => $to) {
            if ($this->filesystem->isDirectory($from)) {
                $this->publishDirectory($from, $to);
            } elseif ($this->filesystem->isFile($from)) {
                $this->publishFile($from, $to);
            } else {
                throw new PublishException(sprintf('Can\'t locate path: "%s".', $from));
            }
        }
    }

    /**
     * Publish the directory to the given directory.
     *
     * @param string $from
     * @param string $to
     */
    public function publishDirectory(string $from, string $to): void
    {
        if (! $this->filesystem->exists($to) || $this->force) {
            $this->status($from, $to, 'Directory');
        }

        // TODO : ajouter un booléen à la méthode "->files()" pour savoir si le retour est un tableau d'object SPlFileInfo ou on son cast le retour en un tableau de string !!!!
        foreach ($this->filesystem->files($from) as $fileInfo) {
            // cast SplFileInfo object to string.
            $file = (string) $fileInfo;
            // copy file or folder.
            $this->publishFile($file, $to . '/' . $this->filesystem->basename($file));
        }
    }

    /**
     * Publish the file to the given path.
     *
     * @param string $from
     * @param string $to
     */
    public function publishFile(string $from, string $to): void
    {
        if (! $this->filesystem->exists($to) || $this->force) {
            $this->createParentDirectory(dirname($to));
            $this->filesystem->copy($from, $to);

            $this->status($from, $to, 'File');
        }
    }

    /**
     * Create the directory to house the published files if needed.
     *
     * @param string $directory
     */
    // TODO : utiliser plutot la méthode $filesystem->ensureDirectoryExists() qui a l'air de faire la même chose que cette méthode !!!!
    private function createParentDirectory(string $directory): void
    {
        if (! $this->filesystem->isDirectory($directory)) {
            $this->filesystem->makeDirectory($directory, 0755, true);
        }
    }

    // TODO : attention il faut vérifier si le callback n'est pas null avant de l'executer car on doit pouvoir utiliser cette méthode sans initialiser de callable !!!! ou alors initialiser le callback avec une "fonction anonyme qui ne fait rien" !!!!
    private function status(string $from, string $to, string $type): void
    {
        call_user_func_array($this->callback, [$from, $to, $type]);
    }



/*
// TODO : exemple pour filtrer sur un tag dans un tableau.
$values[] = ['tag' => 'foo', 'source' => 'source1', 'destination' => 'dest1'];
$values[] = ['tag' => 'bar', 'source' => 'source2', 'destination' => 'dest1'];
$values[] = ['tag' => 'foo', 'source' => 'source3', 'destination' => 'dest1'];

// TODO : eventuellement reporter cette méthode sous le nom "where()" dans une classe Arr::class de support.
function filter($array, callable $callback)
{
    return array_filter($array, $callback, ARRAY_FILTER_USE_BOTH);
}

//https://github.com/antonioribeiro/ia-arr/blob/e0107e68bbce8736f6e19c796a5511b741ced227/src/Support/Traits/EnumeratesValues.php#L538
function whereIn($array, $key, $values, $strict = false)
{
    $values = (array) $values;

    return filter($array, function ($item) use ($key, $values, $strict) {
        return in_array($item[$key] ?? null, $values, $strict);
    });
}

$res = whereIn($values, 'tag', 'foo');

die(var_dump($res));
*/




}
