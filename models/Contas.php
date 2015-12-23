<?php

use Phalcon\Mvc\Collection;

class Contas extends Collection
{

	public $responsavel;
	public $key;
	public $cliente;
	public $host;
	public $database;
  private $caracteres = 'abcdefghijklmnopqrstuvxzwyABCDEFGHIJKLMNOPQRSTUVXZWY0123456789';

  public function initialize()
  {
      $this->setConnectionService('api_contas');
  }

  public function beforeCreate(){
      $string = '';
       for ($i = 0; $i < 30; $i++) {
            $string .= $this->caracteres[rand(0, strlen($this->caracteres) - 1)];
       }
      $this->key = $string;
  }
}