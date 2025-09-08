<?php

require_once dirname(__FILE__) . '/../../MdPesqBuscaProtocoloExterno.php';

// Include DTOs
require_once dirname(__FILE__) . '/../../dto/MdPesqApiKeyDTO.php';
require_once dirname(__FILE__) . '/../../dto/MdPesqApiLogDTO.php';

// Include RNs
require_once dirname(__FILE__) . '/../../rn/MdPesqApiKeyRN.php';
require_once dirname(__FILE__) . '/../../rn/MdPesqApiLogRN.php';

// Include BDs
require_once dirname(__FILE__) . '/../../bd/MdPesqApiKeyBD.php';
require_once dirname(__FILE__) . '/../../bd/MdPesqApiLogBD.php';

// Include API classes
require_once dirname(__FILE__) . '/MdPesqApiController.php';
require_once dirname(__FILE__) . '/controllers/MdPesqProcessoApiController.php';
require_once dirname(__FILE__) . '/controllers/MdPesqDocumentoApiController.php';
require_once dirname(__FILE__) . '/controllers/MdPesqAuthApiController.php';

/**
 * Roteador principal da API REST v1
 * 
 * Este arquivo é o ponto de entrada para todas as chamadas da API REST v1.
 * Ele identifica o endpoint solicitado e direciona para o controlador adequado.
 */
class MdPesqApiRouter extends MdPesqApiController
{
    private $rotas = array();
    
    public function __construct()
    {
        parent::__construct();
        $this->definirRotas();
    }
    
    /**
     * Define todas as rotas disponíveis na API
     */
    private function definirRotas()
    {
        // Rotas de autenticação (não requerem autenticação)
        $this->rotas['GET']['/health'] = array('MdPesqAuthApiController', 'health');
        $this->rotas['POST']['/auth'] = array('MdPesqAuthApiController', 'authenticate');
        
        // Rotas de processos (requerem autenticação)
        $this->rotas['GET']['/processes'] = array('MdPesqProcessoApiController', 'listar');
        $this->rotas['GET']['/processes/{id}'] = array('MdPesqProcessoApiController', 'obter');
        $this->rotas['GET']['/processes/{id}/documents'] = array('MdPesqProcessoApiController', 'listarDocumentos');
        $this->rotas['GET']['/processes/search'] = array('MdPesqProcessoApiController', 'pesquisar');
        
        // Rotas de documentos (requerem autenticação)
        $this->rotas['GET']['/documents/{id}'] = array('MdPesqDocumentoApiController', 'obter');
        $this->rotas['GET']['/documents/{id}/content'] = array('MdPesqDocumentoApiController', 'obterConteudo');
        $this->rotas['GET']['/documents/search'] = array('MdPesqDocumentoApiController', 'pesquisar');
    }
    
    /**
     * Processa a requisição e direciona para o controlador adequado
     */
    public function processar()
    {
        try {
            $metodo = $this->obterMetodoHttp();
            $caminho = $this->obterCaminho();
            
            // Trata requisições OPTIONS para CORS
            if ($metodo === 'OPTIONS') {
                $this->tratarOptions();
                return;
            }
            
            // Registra log da requisição
            $this->registrarLogUso($caminho, $metodo, $_GET);
            
            // Busca a rota correspondente
            $rota = $this->encontrarRota($metodo, $caminho);
            
            if (!$rota) {
                $this->retornarErro(404, 'Endpoint não encontrado', 'ENDPOINT_NOT_FOUND');
                return;
            }
            
            // Verifica se a rota requer autenticação
            if ($this->rotaRequerAutenticacao($caminho)) {
                $this->validarAutenticacao();
            }
            
            // Executa o controlador
            $this->executarControlador($rota, $caminho);
            
        } catch (Exception $e) {
            $this->retornarErro(500, 'Erro interno do servidor', 'INTERNAL_ERROR', $e->getMessage());
        }
    }
    
    /**
     * Obtém o caminho da requisição
     */
    private function obterCaminho()
    {
        $requestUri = $_SERVER['REQUEST_URI'];
        $scriptName = $_SERVER['SCRIPT_NAME'];
        
        // Remove o caminho base do script
        $basePath = dirname($scriptName);
        $path = substr($requestUri, strlen($basePath));
        
        // Remove query string
        if (($pos = strpos($path, '?')) !== false) {
            $path = substr($path, 0, $pos);
        }
        
        // Remove leading slash se presente
        $path = ltrim($path, '/');
        
        // Se vazio, retorna /
        return $path ?: '/';
    }
    
    /**
     * Encontra a rota correspondente ao método e caminho
     */
    private function encontrarRota($metodo, $caminho)
    {
        if (!isset($this->rotas[$metodo])) {
            return null;
        }
        
        foreach ($this->rotas[$metodo] as $padrao => $controlador) {
            $parametros = array();
            
            if ($this->verificarPadrao($padrao, $caminho, $parametros)) {
                return array(
                    'controller' => $controlador,
                    'parameters' => $parametros
                );
            }
        }
        
        return null;
    }
    
    /**
     * Verifica se o caminho corresponde ao padrão da rota
     */
    private function verificarPadrao($padrao, $caminho, &$parametros)
    {
        // Converte padrão para regex
        $regex = preg_replace('/\{([^}]+)\}/', '([^/]+)', $padrao);
        $regex = '#^' . str_replace('/', '\/', $regex) . '$#';
        
        if (preg_match($regex, $caminho, $matches)) {
            // Extrai nomes dos parâmetros
            preg_match_all('/\{([^}]+)\}/', $padrao, $nomes);
            
            // Mapeia valores para nomes
            for ($i = 1; $i < count($matches); $i++) {
                if (isset($nomes[1][$i - 1])) {
                    $parametros[$nomes[1][$i - 1]] = $matches[$i];
                }
            }
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Verifica se a rota requer autenticação
     */
    private function rotaRequerAutenticacao($caminho)
    {
        $rotasPublicas = array('/health', '/auth');
        
        foreach ($rotasPublicas as $rota) {
            if (strpos($caminho, $rota) === 0) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Executa o controlador para a rota
     */
    private function executarControlador($rota, $caminho)
    {
        $classe = $rota['controller'][0];
        $metodo = $rota['controller'][1];
        $parametros = $rota['parameters'];
        
        if (!class_exists($classe)) {
            $this->retornarErro(500, 'Controlador não encontrado', 'CONTROLLER_NOT_FOUND');
            return;
        }
        
        $controlador = new $classe();
        
        if (!method_exists($controlador, $metodo)) {
            $this->retornarErro(500, 'Método não encontrado', 'METHOD_NOT_FOUND');
            return;
        }
        
        // Executa o método do controlador
        call_user_func_array(array($controlador, $metodo), array($parametros));
    }
    
    /**
     * Trata requisições OPTIONS para CORS
     */
    private function tratarOptions()
    {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-API-Key');
        header('Access-Control-Max-Age: 86400');
        http_response_code(200);
        exit();
    }
}

// Processa a requisição se este arquivo for chamado diretamente
if (basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
    try {
        $router = new MdPesqApiRouter();
        $router->processar();
    } catch (Exception $e) {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode(array(
            'success' => false,
            'error' => array(
                'code' => 'ROUTER_ERROR',
                'message' => 'Erro no roteador da API',
                'details' => $e->getMessage()
            ),
            'timestamp' => date('c'),
            'version' => 'v1'
        ));
    }
}