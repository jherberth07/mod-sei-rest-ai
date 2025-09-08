<?php

/**
 * Controlador base para API REST v1 do módulo Pesquisa Pública
 * 
 * Este controlador fornece funcionalidades básicas para todos os endpoints da API,
 * incluindo autenticação, validação e formatação de respostas.
 */
class MdPesqApiController
{
    const API_VERSION = 'v1';
    const CONTENT_TYPE_JSON = 'application/json';
    
    protected $objInfraParametro;
    protected $objSessaoSEI;
    
    public function __construct()
    {
        $this->objInfraParametro = new InfraParametro(BancoSEI::getInstance());
        $this->inicializarSessao();
    }
    
    /**
     * Inicializa sessão SEI para uso da API
     */
    private function inicializarSessao()
    {
        if (!SessaoSEI::getInstance(false)) {
            session_start();
            SessaoSEI::getInstance(false);
        }
        $this->objSessaoSEI = SessaoSEI::getInstance();
    }
    
    /**
     * Valida autenticação da API via API Key
     */
    public function validarAutenticacao()
    {
        $apiKey = $this->obterApiKey();
        
        if (empty($apiKey)) {
            $this->retornarErro(401, 'API key é obrigatória', 'MISSING_API_KEY');
        }
        
        if (!$this->validarApiKey($apiKey)) {
            $this->retornarErro(401, 'API key inválida', 'INVALID_API_KEY');
        }
        
        return true;
    }
    
    /**
     * Obtém API key do header da requisição
     */
    private function obterApiKey()
    {
        $headers = getallheaders();
        
        if (isset($headers['X-API-Key'])) {
            return $headers['X-API-Key'];
        }
        
        if (isset($headers['Authorization'])) {
            if (preg_match('/Bearer\s+(.*)$/i', $headers['Authorization'], $matches)) {
                return $matches[1];
            }
        }
        
        return null;
    }
    
    /**
     * Valida se a API key é válida
     */
    private function validarApiKey($apiKey)
    {
        try {
            $objMdPesqApiKeyDTO = new MdPesqApiKeyDTO();
            $objMdPesqApiKeyDTO->setStrApiKey($apiKey);
            $objMdPesqApiKeyDTO->setStrSinAtivo('S');
            $objMdPesqApiKeyDTO->retNumIdApiKey();
            $objMdPesqApiKeyDTO->retNumIdContato();
            $objMdPesqApiKeyDTO->retDthUltimoAcesso();
            
            $objMdPesqApiKeyRN = new MdPesqApiKeyRN();
            $objResult = $objMdPesqApiKeyRN->consultar($objMdPesqApiKeyDTO);
            
            if ($objResult) {
                // Atualiza último acesso
                $this->atualizarUltimoAcesso($objResult->getNumIdApiKey());
                return true;
            }
            
        } catch (Exception $e) {
            return false;
        }
        
        return false;
    }
    
    /**
     * Atualiza o último acesso da API key
     */
    private function atualizarUltimoAcesso($idApiKey)
    {
        try {
            $objMdPesqApiKeyDTO = new MdPesqApiKeyDTO();
            $objMdPesqApiKeyDTO->setNumIdApiKey($idApiKey);
            $objMdPesqApiKeyDTO->setDthUltimoAcesso(InfraData::getStrDataHoraAtual());
            
            $objMdPesqApiKeyRN = new MdPesqApiKeyRN();
            $objMdPesqApiKeyRN->alterar($objMdPesqApiKeyDTO);
            
        } catch (Exception $e) {
            // Log do erro mas não interrompe o processo
            InfraDebug::getInstance()->gravar('Erro ao atualizar último acesso: ' . $e->getMessage());
        }
    }
    
    /**
     * Retorna resposta de sucesso formatada
     */
    public function retornarSucesso($dados = null, $mensagem = 'Sucesso', $codigo = 200)
    {
        $resposta = array(
            'success' => true,
            'message' => $mensagem,
            'data' => $dados,
            'timestamp' => date('c'),
            'version' => self::API_VERSION
        );
        
        $this->enviarResposta($resposta, $codigo);
    }
    
    /**
     * Retorna resposta de erro formatada
     */
    public function retornarErro($codigo, $mensagem, $codigoErro = null, $detalhes = null)
    {
        $resposta = array(
            'success' => false,
            'error' => array(
                'code' => $codigoErro ?: 'ERROR_' . $codigo,
                'message' => $mensagem,
                'details' => $detalhes
            ),
            'timestamp' => date('c'),
            'version' => self::API_VERSION
        );
        
        $this->enviarResposta($resposta, $codigo);
    }
    
    /**
     * Envia resposta HTTP formatada
     */
    private function enviarResposta($dados, $codigo = 200)
    {
        header('Content-Type: ' . self::CONTENT_TYPE_JSON);
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-API-Key');
        http_response_code($codigo);
        
        echo json_encode($dados, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit();
    }
    
    /**
     * Obtém método HTTP da requisição
     */
    public function obterMetodoHttp()
    {
        return strtoupper($_SERVER['REQUEST_METHOD']);
    }
    
    /**
     * Obtém dados do corpo da requisição
     */
    public function obterDadosRequisicao()
    {
        $input = file_get_contents('php://input');
        
        if (empty($input)) {
            return array();
        }
        
        $dados = json_decode($input, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->retornarErro(400, 'JSON inválido no corpo da requisição', 'INVALID_JSON');
        }
        
        return $dados ?: array();
    }
    
    /**
     * Valida parâmetros obrigatórios
     */
    public function validarParametros($parametros, $obrigatorios)
    {
        $faltando = array();
        
        foreach ($obrigatorios as $param) {
            if (!isset($parametros[$param]) || empty($parametros[$param])) {
                $faltando[] = $param;
            }
        }
        
        if (!empty($faltando)) {
            $this->retornarErro(400, 'Parâmetros obrigatórios não informados: ' . implode(', ', $faltando), 'MISSING_PARAMETERS');
        }
        
        return true;
    }
    
    /**
     * Registra log de uso da API
     */
    public function registrarLogUso($endpoint, $metodo, $parametros = null)
    {
        try {
            $objMdPesqApiLogDTO = new MdPesqApiLogDTO();
            $objMdPesqApiLogDTO->setStrEndpoint($endpoint);
            $objMdPesqApiLogDTO->setStrMetodo($metodo);
            $objMdPesqApiLogDTO->setStrParametros($parametros ? json_encode($parametros) : null);
            $objMdPesqApiLogDTO->setDthAcesso(InfraData::getStrDataHoraAtual());
            $objMdPesqApiLogDTO->setStrIpUsuario($_SERVER['REMOTE_ADDR']);
            $objMdPesqApiLogDTO->setStrUserAgent($_SERVER['HTTP_USER_AGENT'] ?? null);
            
            $objMdPesqApiLogRN = new MdPesqApiLogRN();
            $objMdPesqApiLogRN->cadastrar($objMdPesqApiLogDTO);
            
        } catch (Exception $e) {
            // Log do erro mas não interrompe o processo
            InfraDebug::getInstance()->gravar('Erro ao registrar log de uso: ' . $e->getMessage());
        }
    }
}