<?php 

class Produtos extends \Phalcon\Mvc\Collection
{
    public $nome;
    public $categoria;
    public $destaque = 0;
    public $ativo = 1;
    public $meta_title;
    public $meta_description;
    public $meta_keywords;
    public $resumo;
    public $descricao;
    public $relacionados = array();

    public function initialize()
    {
        $this->setConnectionService('api_db');
    }


    public function getSource()
    {
        return "produtos";
    }

    public function beforeCreate()
    {
        // Set the creation date
        $this->created_at = date('Y-m-d H:i:s');
    }

    public function beforeUpdate()
    {
        // Set the modification date
        $this->modified_in = date('Y-m-d H:i:s');
    }

}