<?php

use Phalcon\Mvc\Micro;
use Phalcon\Di\FactoryDefault;
use Phalcon\Loader;
use Phalcon\Http\Response;

$app = new Micro();

$di = new FactoryDefault();

// Autoloading Models
$loader = new Loader();

$loader->registerDirs(
    array(
        __DIR__ . '/models/'
    )
)->register();

$di->set('collectionManager', function(){
    return new Phalcon\Mvc\Collection\Manager();
}, true);

$di->set(
    'api_contas',
    function () {
        $mongo = new MongoClient("mongodb://localhost");
        return $mongo->selectDB("api_contas");
    },
    true
);

function setDatabase($di,$host,$db){ 
    $di->set(
        'api_db',
        function () use ($host,$db) {
            $mongo = new MongoClient($host);
            return $mongo->selectDB($db);
        },
        true
    );

}

function setProduto($obj){ 
    $p = new Produtos;
    $p->sku = (string) $obj->codigo;
    $p->nome = (string) $obj->nome;
    $p->categoria = (string) $obj->categoria;
    $p->destaque = (string) $obj->destaque;
    $p->ativo = (string) $obj->ativo;
    if(!isset($obj->detalhes)){
        $p->valor = floatval($obj->valor);
        $p->estoque = intval($obj->estoque);
    }else{
        foreach ($obj->detalhes->detalhe as $key => $value) {
             $p->detalhes[] = $value;
        }
        foreach ($p->detalhes as $key => $value) {
            $value->detalhe_id = (string) new MongoId();
        }
    }
    $p->resumo = (string) $obj->resumo;
    $p->descricao = (string) $obj->descricao;
    $p->peso = (string) $obj->cubagem->peso;
    $p->altura = (string) $obj->cubagem->altura;
    $p->largura = (string) $obj->cubagem->largura;
    $p->comprimento = (string) $obj->cubagem->comprimento;
    $erros = array();
    if(!$p->save()){
        $erros['codigo_produto'] = $obj->codigo;
        foreach ($user->getMessages() as $message) {
            $erros['mensagem'][] = $message->getMessage();
        }
        return $erros;
    }else{
        return true;
    }
}

function setCategoria($obj){ 
    $c = new Categorias;
    $c->sku = (string) $obj->codigo;
    $c->nome = (string) $obj->nome;
    $c->parent_sku = (string) $obj->parent;
    $erros = array();
    if(!$c->save()){
        $erros['codigo_produto'] = $obj->codigo;
        foreach ($user->getMessages() as $message) {
            $erros['mensagem'][] = $message->getMessage();
        }
        return $erros;
    }else{
        return true;
    }
}

$app->get('/', function () {
    echo "<h1>Api Sualoja.online!</h1>";
});

##################################################
####### PRODUTOS
###################################################

// Retorna todos os produtos cadastrados na loja
$app->get('/products/{key}',function($key) use($app,$di){
    $response = new Response();
    $response->setHeader('Content-Type', 'application/xml');
    $conta = Contas::findFirst(array('conditions' => array('key' => $key)));
    if($conta){
        setDatabase($di,$conta->host,$conta->database);
        $dados = Produtos::find();
        $xml = new SimpleXMLElement("<?xml version='1.0' encoding='ISO-8859-1'?><response/>");
        $xml->addChild('status','OK');
        $produtos = $xml->addChild('produtos');
        foreach ($dados as $key => $value) {
            $produto = $produtos->addChild('produto');
            $produto->addChild('codigo',$value->sku);
            $produto->addChild('nome',$value->nome);
            $produto->addChild('categoria',$value->categoria);
            $produto->addChild('valor',$value->valor);
            $produto->addChild('destaque',$value->destaque);
            $produto->addChild('ativo',$value->ativo);
            $produto->addChild('estoque',$value->estoque);
            $produto->addChild('resumo',$value->resumo);
            $produto->addChild('descricao',$value->descricao);
            $cubagem = $produto->addChild('cubagem');
            $cubagem->addChild('peso',$value->peso);
            $cubagem->addChild('altura',$value->altura);
            $cubagem->addChild('largura',$value->largura);
            $cubagem->addChild('comprimento',$value->comprimento);

        }
        $response->setStatusCode(200, 'OK');
        $response->setContent($xml->asXml());
    }else{
        $response->setStatusCode(401, 'Não autorizado');
        $response->setContent("<?xml version='1.0' encoding='ISO-8859-1'?><response><status>ERROR</status><mensagem>Chave inválida</mensagem></response>");
    }
    return $response;
});

// Retorna um produto especifico cadastrado na loja
$app->get('/product/{key}/{codigo}',function($key,$codigo) use($app,$di){
    $response = new Response();
    $response->setHeader('Content-Type', 'application/xml');
    $conta = Contas::findFirst(array('conditions' => array('key' => $key)));
    if($conta){
        setDatabase($di,$conta->host,$conta->database);
        $dados = Produtos::findFirst(
            array(
                'conditions' => array('sku' => $codigo)
                )
            );
        if($dados){
            $xml = new SimpleXMLElement("<?xml version='1.0' encoding='ISO-8859-1'?> <response/>");
            $xml->addChild('status','OK');
            $xml->addChild('codigo',(string)$dados->sku);
            $xml->addChild('nome',(string)$dados->nome);
            $xml->addChild('categoria',(string)$dados->categoria);
            $xml->addChild('valor',floatval($dados->valor));
            $xml->addChild('destaque',intval($dados->destaque));
            $xml->addChild('ativo',(string)$dados->ativo);
            $xml->addChild('estoque',(string)$dados->estoque);
            $xml->addChild('resumo',(string)$dados->resumo);
            $xml->addChild('descricao',(string)$dados->descricao);
            $cubagem = $xml->addChild('cubagem');
            $cubagem->addChild('peso',(string)$dados->peso);
            $cubagem->addChild('altura',(string)$dados->altura);
            $cubagem->addChild('largura',(string)$dados->largura);
            $cubagem->addChild('comprimento',(string)$dados->comprimento);
            $response->setStatusCode(200, 'OK');
            $response->setContent($xml->asXml());
        }else{
            $response->setStatusCode(200, 'OK');
            $response->setContent("<?xml version='1.0' encoding='ISO-8859-1'?><response><status>ERROR</status><mensagem>Produto cod. $codigo não encontrado</mensagem></response>");
        }
    }else{
        $response->setStatusCode(401, 'Não autorizado');
        $response->setContent("<?xml version='1.0' encoding='ISO-8859-1'?><response><status>ERROR</status><mensagem>Chave inválida</mensagem></response>");
    }
    return $response;
});

// Metodo responsavel por validar o usuario e salvar o produto;
// Retorna a quantidade de produtos criados
$app->post('/create/products/{key}', function ($key) use ($app,$di) {
    $response = new Response();
    $response->setHeader('Content-Type', 'application/xml');
    $conta = Contas::findFirst(array('conditions' => array('key' => $key)));
    if($conta){
        $xml = simplexml_load_string($_POST['xml']);
        setDatabase($di,$conta->host,$conta->database);
        $erros = array();
        $total = 0;
        foreach ($xml->produto as $key => $value) {
            $status = setProduto($value);
            if(!$status){
                $erros[] = $status;
            }else{
                $total += 1;
            }
        }
        $response->setStatusCode(201, 'Created');
        $response->setContent("<?xml version='1.0' encoding='ISO-8859-1'?><response><status>OK</status><mensagem>Foram criados $total produtos de um total de ".count($xml)." de produtos enviados</mensagem></response>");
    }else{
        $response->setStatusCode(401, 'Não autorizado');
        $response->setContent("<?xml version='1.0' encoding='ISO-8859-1'?><response><status>ERROR</status><mensagem>Chave inválida</mensagem></response>");
    }
    return $response;
});

// Update produto
$app->post('/update/product/{key}/{codigo}', function ($key,$codigo) use ($app,$di) {
    $response = new Response();
    $conta = Contas::findFirst(array('conditions' => array('key' => $key)));
    if($conta){
        setDatabase($di,$conta->host,$conta->database);
        $produto = Produtos::findFirst(
            array(
                'conditions' => array('sku' => $codigo)
                )
            );
        if($produto){
            $xml = simplexml_load_string($_POST['xml']);
            if(!isset($xml->detalhe)){
                $produto->estoque = intval($xml->estoque);
                $produto->valor = floatval($xml->valor);
            }else{
                $chave = null;
                foreach ($produto->detalhes as $key => $value) {
                    if($value['codigo'] == $xml->detalhe->codigo){
                        $chave = $key;
                    }
                }
                if(!is_null($chave)){
                    $produto->detalhes[$chave]['estoque'] = intval($xml->detalhe->estoque);
                    $produto->detalhes[$chave]['valor'] = floatval($xml->detalhe->valor);
                }else{
                    $response->setStatusCode(400, 'Invalid Request');
                    $response->setContent("<?xml version='1.0' encoding='ISO-8859-1'?><response><status>ERROR</status><mensagem>Detalhe não encontrado</mensagem></response>");
                    return $response;
                }
            }
            if($produto->save()){
                $response->setStatusCode(200, 'OK');
                $response->setContent("<?xml version='1.0' encoding='ISO-8859-1'?><response><status>OK</status><mensagem>Produto $codigo alterado com sucesso</mensagem></response>");
            }else{
                $response->setStatusCode(400, 'Invalid Request');
                $errors = array();
                foreach ($produto->getMessages() as $message) {
                    $errors[] = $message->getMessage();
                }
                $response->setContent("<?xml version='1.0' encoding='ISO-8859-1'?><response><status>ERROR</status><mensagem>{$errors[0]}</mensagem></response>");
            }
        }else{
            $response->setStatusCode(200, 'OK');
            $response->setContent("<?xml version='1.0' encoding='ISO-8859-1'?><response><status>ERROR</status><mensagem>Produto $codigo não encontrado</mensagem></response>");
        }
    }else{
        $response->setStatusCode(401, 'Não autorizado');
        $response->setContent("<?xml version='1.0' encoding='ISO-8859-1'?><response><status>ERROR</status><mensagem>Chave inválida</mensagem></response>");
    }
    return $response;
});

###################################################
####### Categorias
###################################################

// Retorna todos os produtos cadastrados na loja
$app->get('/categories/{key}',function($key) use($app,$di){
    $response = new Response();
    $response->setHeader('Content-Type', 'application/xml');
    $conta = Contas::findFirst(array('conditions' => array('key' => $key)));
    if($conta){
        setDatabase($di,$conta->host,$conta->database);
        $dados = Categorias::find();
        $xml = new SimpleXMLElement("<?xml version='1.0' encoding='ISO-8859-1'?><response/>");
        $xml->addChild('status','OK');
        $categorias = $xml->addChild('categorias');
        foreach ($dados as $key => $value) {
            $categoria = $categorias->addChild('categoria');
            $categoria->addChild('codigo',$value->sku);
            $categoria->addChild('nome',$value->nome);
            $categoria->addChild('parent',$value->parent_sku);
        }
        $response->setStatusCode(200, 'OK');
        $response->setContent($xml->asXml());
    }else{
        $response->setStatusCode(401, 'Não autorizado');
        $response->setContent("<?xml version='1.0' encoding='ISO-8859-1'?><response><status>ERROR</status><mensagem>Chave inválida</mensagem></response>");
    }
    return $response;
});

// Retorna uma categoria especifica cadastrado na loja
$app->get('/category/{key}/{codigo}',function($key,$codigo) use($app,$di){
    $response = new Response();
    $response->setHeader('Content-Type', 'application/xml');
    $conta = Contas::findFirst(array('conditions' => array('key' => $key)));
    if($conta){
        setDatabase($di,$conta->host,$conta->database);
        $dados = Categorias::findFirst(
            array(
                'conditions' => array('sku' => $codigo)
                )
            );
        if($dados){
            $xml = new SimpleXMLElement("<?xml version='1.0' encoding='ISO-8859-1'?> <response/>");
            $xml->addChild('status','OK');
            $xml->addChild('codigo',$dados->sku);
            $xml->addChild('nome',$dados->nome);
            $xml->addChild('parent',$dados->parent_sku);
            $response->setStatusCode(200, 'OK');
            $response->setContent($xml->asXml());
        }else{
            $response->setStatusCode(200, 'OK');
            $response->setContent("<?xml version='1.0' encoding='ISO-8859-1'?><response><status>ERROR</status><mensagem>Categoria cod. $codigo não encontrado</mensagem></response>");
        }
    }else{
        $response->setStatusCode(401, 'Não autorizado');
        $response->setContent("<?xml version='1.0' encoding='ISO-8859-1'?><response><status>ERROR</status><mensagem>Chave inválida</mensagem></response>");
    }
    return $response;
});

// Metodo responsavel por validar o usuario e salvar as categorias;
// Retorna a quantidade de categoria criados
$app->post('/create/categories/{key}', function ($key) use ($app,$di) {
    $response = new Response();
    $response->setHeader('Content-Type', 'application/xml');
    $conta = Contas::findFirst(array('conditions' => array('key' => $key)));
    if($conta){
        $xml = simplexml_load_string($_POST['xml']);
        setDatabase($di,$conta->host,$conta->database);
        $erros = array();
        $total = 0;
        foreach ($xml->categoria as $key => $value) {
            $status = setCategoria($value);
            if(!$status){
                $erros[] = $status;
            }else{
                $total += 1;
            }
        }
        $response->setStatusCode(201, 'Created');
        $response->setContent("<?xml version='1.0' encoding='ISO-8859-1'?><response><status>OK</status><mensagem>Foram criadas $total categorias de um total de ".count($xml)." de produtos enviados</mensagem></response>");
    }else{
        $response->setStatusCode(401, 'Não autorizado');
        $response->setContent("<?xml version='1.0' encoding='ISO-8859-1'?><response><status>ERROR</status><mensagem>Chave inválida</mensagem></response>");
    }
    return $response;
});

// Update Categoria
$app->post('/update/category/{key}/{codigo}', function ($key,$codigo) use ($app,$di) {
    $response = new Response();
    $conta = Contas::findFirst(array('conditions' => array('key' => $key)));
    if($conta){
        setDatabase($di,$conta->host,$conta->database);
        $categoria = Categorias::findFirst(
            array(
                'conditions' => array('sku' => $codigo)
                )
            );
        if($categoria){
            $xml = simplexml_load_string($_POST['xml']);
            $categoria->nome = (string) $xml->nome;
            if($categoria->save()){
                $response->setStatusCode(200, 'OK');
                $response->setContent("<?xml version='1.0' encoding='ISO-8859-1'?><response><status>OK</status><mensagem>Categoria $codigo alterada com sucesso</mensagem></response>");
            }else{
                $response->setStatusCode(400, 'Invalid Request');
                $errors = array();
                foreach ($categoria->getMessages() as $message) {
                    $errors[] = $message->getMessage();
                }
                $response->setContent("<?xml version='1.0' encoding='ISO-8859-1'?><response><status>ERROR</status><mensagem>{$errors[0]}</mensagem></response>");
            }
        }else{
            $response->setStatusCode(200, 'OK');
            $response->setContent("<?xml version='1.0' encoding='ISO-8859-1'?><response><status>ERROR</status><mensagem>Categoria $codigo não encontrada</mensagem></response>");
        }
    }else{
        $response->setStatusCode(401, 'Não autorizado');
        $response->setContent("<?xml version='1.0' encoding='ISO-8859-1'?><response><status>ERROR</status><mensagem>Chave inválida</mensagem></response>");
    }
    return $response;
});

###################################################
####### CRIAÇÃO DO ACESSO
###################################################

// Cria o usuario e retorna a chave de acesso.
$app->post('/create/account', function () use ($app) {
    $user = new Contas;
    $user->responsavel = $app->request->getPost('responsavel');
    $user->cliente = $app->request->getPost('cliente');
    $user->host = $app->request->getPost('host');
    $user->database = $app->request->getPost('database');
    $response = new Response();
    $response->setHeader('Content-Type', 'application/xml');
    if($user->save()){
        $response->setStatusCode(201, 'Created');
        $response->setContent("<?xml version='1.0' encoding='ISO-8859-1'?><response><status>OK</status><mensagem>{$user->key}</mensagem></response>");
    }else{
        $response->setStatusCode(400, 'Invalid Request');
        $errors = array();
        foreach ($user->getMessages() as $message) {
            $errors[] = $message->getMessage();
        }
        $response->setContent("<?xml version='1.0' encoding='ISO-8859-1'?><response><status>ERROR</status><mensagem>{$errors[0]}</mensagem></response>");
    }
    return $response;
});

##################################################
####### NOT FOUND
##################################################

// Caso a url acessada não existe retorna uma mensagem de erro.
$app->notFound(function () use ($app) {
    $response = new Response();
    $response->setStatusCode(404, 'Not Found');
    $response->setContent("<?xml version='1.0' encoding='ISO-8859-1'?><response><status>ERROR</status><mensagem>Requisição inválida, m´dtodo não encontrado </mensagem></response>");
    return $response;
});

$app->handle();
