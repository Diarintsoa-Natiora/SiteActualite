<?php

function getUrl(string $slug, int $id, int $idCategory): string
{
    return '/actualites/' . $slug . '-' . $id . '-' . $idCategory . '.html';
}
