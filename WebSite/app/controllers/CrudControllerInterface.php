<?php

namespace app\controllers;

interface CrudControllerInterface
{
    public function index();
    public function create();
    public function edit($id);
    public function delete($id);
}
