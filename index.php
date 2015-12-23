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
    $p->valor = floatval($obj->valor);
    $p->estoque = intval($obj->estoque);
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


$app->get('/', function () {
    echo "<h1>Api Sualoja.online!</h1>";
});

// Retorna todos os produtos cadastrados na loja
$app->get('/products/{key}',function($key) use($app,$di){
    $response = new Response();
    $conta = Contas::findFirst(array('conditions' => array('key' => $key)));
    if($conta){
        setDatabase($di,$conta->host,$conta->database);
        $dados = Produtos::find();
        $xml = new SimpleXMLElement("<?xml version='1.0' encoding='ISO-8859-1'?> <items/>");
        foreach ($dados as $key => $value) {
            $produto = $xml->addChild('produtos');
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
        $response->setJsonContent(
            array(
                'status' => 'OK',
                'response'   => array('produtos' => $xml->asXml()),
            )
        );
    }else{
        $response->setStatusCode(401, 'Não autorizado');
        $response->setJsonContent(
            array(
                'status' => 'Error',
                'response'   => array('Chave invalida'),
            )
        );
    }
    return $response;
});


// Retorna um produto especifo cadastrado na loja
$app->get('/product/{key}/{codigo}',function($key,$codigo) use($app,$di){
    $response = new Response();
    $conta = Contas::findFirst(array('conditions' => array('key' => $key)));
    if($conta){
        setDatabase($di,$conta->host,$conta->database);
        $dados = Produtos::findFirst(
            array(
                'conditions' => array('sku' => $codigo)
                )
            );
        if($dados){
            $xml = new SimpleXMLElement("<?xml version='1.0' encoding='ISO-8859-1'?> <item/>");
            $xml->addChild('codigo',$dados->sku);
            $xml->addChild('nome',$dados->nome);
            $xml->addChild('categoria',$dados->categoria);
            $xml->addChild('valor',$dados->valor);
            $xml->addChild('destaque',$dados->destaque);
            $xml->addChild('ativo',$dados->ativo);
            $xml->addChild('estoque',$dados->estoque);
            $xml->addChild('resumo',$dados->resumo);
            $xml->addChild('descricao',$dados->descricao);
            $cubagem = $xml->addChild('cubagem');
            $cubagem->addChild('peso',$dados->peso);
            $cubagem->addChild('altura',$dados->altura);
            $cubagem->addChild('largura',$dados->largura);
            $cubagem->addChild('comprimento',$dados->comprimento);
            $response->setStatusCode(200, 'OK');
            $response->setJsonContent(
                array(
                    'status' => 'OK',
                    'response'   => array('produto' => $xml->asXml()),
                )
            );
        }else{
            $response->setStatusCode(200, 'OK');
            $response->setJsonContent(
                array(
                    'status' => 'Error',
                    'response'   => array('Nenhum produto encontrado'),
                )
            );  
        }
    }else{
        $response->setStatusCode(401, 'Não autorizado');
        $response->setJsonContent(
            array(
                'status' => 'Error',
                'response'   => array('Chave invalida'),
            )
        );
    }
    return $response;
});

// Metodo responsavel por validar o usuario e salvar o produto;
// Retorna a quantidade de produtos criados
$app->post('/create/product/{key}', function ($key) use ($app,$di) {
    $response = new Response();
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
        $retorno['status'] = 'OK';
        $retorno['response'] = array('Foram criados '.$total.' produtos de um total de '.count($xml).' de produtos enviados');
        if(!empty($erros)){
            $retorno['erros'] = $erros;
        }
        $response->setStatusCode(201, 'Created');
        $response->setJsonContent(
           $retorno
        );
    }else{
        $response->setStatusCode(401, 'Não autorizado');
        $response->setJsonContent(
            array(
                'status' => 'Error',
                'response'   => array('Chave invalida'),
            )
        );
    }
    return $response;
});


//Metodo responsavel por criar a conta para o usuario.
// Retorna a chave de acesso
$app->post('/create/account', function () use ($app) {
    $user = new Contas;
    $user->responsavel = $app->request->getPost('responsavel');
    $user->cliente = $app->request->getPost('cliente');
    $user->host = $app->request->getPost('host');
    $user->database = $app->request->getPost('database');
    $response = new Response();
    if($user->save()){
        $response->setStatusCode(201, 'Created');
        $response->setJsonContent(
            array(
                'status' => 'OK',
                'response'   => $user->key,
            )
        );
    }else{
        $response->setStatusCode(400, 'Invalid Request');
        $errors = array();
        foreach ($user->getMessages() as $message) {
            $errors[] = $message->getMessage();
        }

        $response->setJsonContent(
            array(
                'status' => 'Error',
                'response'   => $erros,
            )
        );
    }
    return $response;
});

// Update Product
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
            $produto->estoque = intval($xml->estoque);
            $produto->valor = floatval($xml->valor);
            if($produto->save()){
                $response->setStatusCode(201, 'Created');
                $response->setJsonContent(
                    array(
                        'status' => 'OK',
                        'response'   => array('Produto alterado com sucesso'),
                    )
                );
            }else{
                $response->setStatusCode(400, 'Invalid Request');
                $errors = array();
                foreach ($produto->getMessages() as $message) {
                    $errors[] = $message->getMessage();
                }

                $response->setJsonContent(
                    array(
                        'status' => 'Error',
                        'response'   => $erros,
                    )
                );
            }
        }else{
            $response->setStatusCode(200, 'OK');
            $response->setJsonContent(
                array(
                    'status' => 'Error',
                    'response'   => array('Nenhum produto encontrado'),
                )
            );  
        }
    }else{
        $response->setStatusCode(401, 'Não autorizado');
        $response->setJsonContent(
            array(
                'status' => 'Error',
                'response'   => array('Chave invalida'),
            )
        );
    }
    return $response;
});


$app->notFound(function () use ($app) {
	$app->response->setStatusCode(404, 'Not Found')->sendHeaders();
	echo 'Pagina não encontrada';
});

$app->handle();
