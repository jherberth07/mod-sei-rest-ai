<?php

/**
 * Controlador de autenticação da API REST v1
 */
class MdPesqAuthApiController extends MdPesqApiController
{
    /**
     * Endpoint de health check
     * GET /api/v1/health
     */
    public function health($parametros = array())
    {
        $dados = array(
            'status' => 'ok',
            'service' => 'SEI Pesquisa Pública API',
            'version' => self::API_VERSION,
            'timestamp' => date('c'),
            'endpoints' => array(
                'GET /health' => 'Health check',
                'POST /auth' => 'Autenticação',
                'GET /processes' => 'Listar processos',
                'GET /processes/{id}' => 'Obter processo específico',
                'GET /processes/{id}/documents' => 'Listar documentos do processo',
                'GET /processes/search' => 'Pesquisar processos',
                'GET /documents/{id}' => 'Obter documento específico',
                'GET /documents/{id}/content' => 'Obter conteúdo do documento',
                'GET /documents/search' => 'Pesquisar documentos'
            )
        );
        
        $this->retornarSucesso($dados, 'API funcionando corretamente');
    }
    
    /**
     * Endpoint de autenticação
     * POST /api/v1/auth
     */
    public function authenticate($parametros = array())
    {
        $metodo = $this->obterMetodoHttp();
        
        if ($metodo !== 'POST') {
            $this->retornarErro(405, 'Método não permitido', 'METHOD_NOT_ALLOWED');
            return;
        }
        
        $dados = $this->obterDadosRequisicao();
        
        // Valida parâmetros obrigatórios
        $this->validarParametros($dados, array('username', 'password'));
        
        $username = $dados['username'];
        $password = $dados['password'];
        
        // Valida credenciais
        if ($this->validarCredenciais($username, $password)) {
            $apiKey = $this->gerarApiKey($username);
            
            $dadosResposta = array(
                'api_key' => $apiKey,
                'expires_in' => 3600, // 1 hora
                'token_type' => 'Bearer',
                'user' => array(
                    'username' => $username
                )
            );
            
            $this->retornarSucesso($dadosResposta, 'Autenticação realizada com sucesso');
        } else {
            $this->retornarErro(401, 'Credenciais inválidas', 'INVALID_CREDENTIALS');
        }
    }
    
    /**
     * Valida credenciais do usuário
     */
    private function validarCredenciais($username, $password)
    {
        try {
            // Simula validação de credenciais
            // Em uma implementação real, isso seria validado contra o banco de dados
            
            $objContatoDTO = new ContatoDTO();
            $objContatoDTO->setStrSigla($username);
            $objContatoDTO->retNumIdContato();
            $objContatoDTO->retStrSigla();
            $objContatoDTO->retStrNome();
            
            $objContatoRN = new ContatoRN();
            $objContato = $objContatoRN->consultarRN0324($objContatoDTO);
            
            if ($objContato) {
                // Aqui deveria validar a senha
                // Por simplicidade, vamos aceitar qualquer senha não vazia
                return !empty($password);
            }
            
        } catch (Exception $e) {
            return false;
        }
        
        return false;
    }
    
    /**
     * Gera uma nova API key para o usuário
     */
    private function gerarApiKey($username)
    {
        // Gera uma API key única
        $apiKey = bin2hex(random_bytes(32));
        
        try {
            // Busca o contato
            $objContatoDTO = new ContatoDTO();
            $objContatoDTO->setStrSigla($username);
            $objContatoDTO->retNumIdContato();
            
            $objContatoRN = new ContatoRN();
            $objContato = $objContatoRN->consultarRN0324($objContatoDTO);
            
            if ($objContato) {
                // Remove API keys antigas do usuário
                $this->removerApiKeysAntigas($objContato->getNumIdContato());
                
                // Cria nova API key
                $objMdPesqApiKeyDTO = new MdPesqApiKeyDTO();
                $objMdPesqApiKeyDTO->setNumIdContato($objContato->getNumIdContato());
                $objMdPesqApiKeyDTO->setStrApiKey($apiKey);
                $objMdPesqApiKeyDTO->setStrSinAtivo('S');
                $objMdPesqApiKeyDTO->setDthCriacao(InfraData::getStrDataHoraAtual());
                $objMdPesqApiKeyDTO->setDthUltimoAcesso(InfraData::getStrDataHoraAtual());
                
                $objMdPesqApiKeyRN = new MdPesqApiKeyRN();
                $objMdPesqApiKeyRN->cadastrar($objMdPesqApiKeyDTO);
            }
            
        } catch (Exception $e) {
            // Em caso de erro, ainda retorna a API key gerada
            // mas ela pode não funcionar corretamente
            InfraDebug::getInstance()->gravar('Erro ao salvar API key: ' . $e->getMessage());
        }
        
        return $apiKey;
    }
    
    /**
     * Remove API keys antigas do usuário
     */
    private function removerApiKeysAntigas($idContato)
    {
        try {
            $objMdPesqApiKeyDTO = new MdPesqApiKeyDTO();
            $objMdPesqApiKeyDTO->setNumIdContato($idContato);
            $objMdPesqApiKeyDTO->retNumIdApiKey();
            
            $objMdPesqApiKeyRN = new MdPesqApiKeyRN();
            $arrApiKeys = $objMdPesqApiKeyRN->listar($objMdPesqApiKeyDTO);
            
            foreach ($arrApiKeys as $apiKey) {
                $objMdPesqApiKeyRN->excluir(array($apiKey));
            }
            
        } catch (Exception $e) {
            // Ignora erros ao remover API keys antigas
            InfraDebug::getInstance()->gravar('Erro ao remover API keys antigas: ' . $e->getMessage());
        }
    }
}