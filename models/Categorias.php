<?php
class Categorias extends \Phalcon\Mvc\Collection
{
    public $nome;
    public $parent;
    public $subcategorias = array();
    public $produtos = array();

     public function initialize()
    {
        $this->setConnectionService('api_db');
    }

    public function getSource()
    {
        return "categorias";
    }
    

    public function beforeCreate()
    {
        // Set the creation date
        $this->created_at = date('Y-m-d H:i:s');
    }

    public function beforeSave()
    {   
        $categoria = self::findFirst(array(
            'conditions' => array(
                'sku' => $this->parent_sku
            )
        ));
        if($categoria){
            $this->parent = $categoria->_id;
        }
        if($this->parent != ''){
            $pai = Categorias::findById($this->parent);
            if(!empty($pai)){
                array_push($pai->subcategorias, $this->getId());
                $pai->subcategorias = array_unique($pai->subcategorias);
                $pai->save();
            }
        }
    }

    public function beforeUpdate()
    {
        // Set the modification date
        $this->modified_in = date('Y-m-d H:i:s');
    }
}