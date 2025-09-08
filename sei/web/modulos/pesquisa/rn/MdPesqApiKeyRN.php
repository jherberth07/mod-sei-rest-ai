<?php

/**
 * Regras de negócio para chaves de API
 */
class MdPesqApiKeyRN extends InfraRN
{
    public function __construct()
    {
        parent::__construct();
    }

    protected function inicializarObjInfraIBanco()
    {
        return BancoSEI::getInstance();
    }

    /**
     * Cadastra uma nova chave de API
     */
    public function cadastrar(MdPesqApiKeyDTO $objMdPesqApiKeyDTO)
    {
        try {
            $this->validarCadastro($objMdPesqApiKeyDTO);
            
            $objMdPesqApiKeyBD = new MdPesqApiKeyBD($this->getObjInfraIBanco());
            $ret = $objMdPesqApiKeyBD->cadastrar($objMdPesqApiKeyDTO);
            
            return $ret;
            
        } catch (Exception $e) {
            throw new InfraException('Erro ao cadastrar chave de API.', $e);
        }
    }

    /**
     * Altera uma chave de API existente
     */
    public function alterar(MdPesqApiKeyDTO $objMdPesqApiKeyDTO)
    {
        try {
            $this->validarAlteracao($objMdPesqApiKeyDTO);
            
            $objMdPesqApiKeyBD = new MdPesqApiKeyBD($this->getObjInfraIBanco());
            $objMdPesqApiKeyBD->alterar($objMdPesqApiKeyDTO);
            
        } catch (Exception $e) {
            throw new InfraException('Erro ao alterar chave de API.', $e);
        }
    }

    /**
     * Exclui chaves de API
     */
    public function excluir($arrObjMdPesqApiKeyDTO)
    {
        try {
            $this->validarExclusao($arrObjMdPesqApiKeyDTO);
            
            $objMdPesqApiKeyBD = new MdPesqApiKeyBD($this->getObjInfraIBanco());
            
            for ($i = 0; $i < count($arrObjMdPesqApiKeyDTO); $i++) {
                $objMdPesqApiKeyBD->excluir($arrObjMdPesqApiKeyDTO[$i]);
            }
            
        } catch (Exception $e) {
            throw new InfraException('Erro ao excluir chave(s) de API.', $e);
        }
    }

    /**
     * Consulta uma chave de API
     */
    public function consultar(MdPesqApiKeyDTO $objMdPesqApiKeyDTO)
    {
        try {
            $this->validarConsultar($objMdPesqApiKeyDTO);
            
            $objMdPesqApiKeyBD = new MdPesqApiKeyBD($this->getObjInfraIBanco());
            $ret = $objMdPesqApiKeyBD->consultar($objMdPesqApiKeyDTO);
            
            return $ret;
            
        } catch (Exception $e) {
            throw new InfraException('Erro ao consultar chave de API.', $e);
        }
    }

    /**
     * Lista chaves de API
     */
    public function listar(MdPesqApiKeyDTO $objMdPesqApiKeyDTO)
    {
        try {
            $this->validarListar($objMdPesqApiKeyDTO);
            
            $objMdPesqApiKeyBD = new MdPesqApiKeyBD($this->getObjInfraIBanco());
            $ret = $objMdPesqApiKeyBD->listar($objMdPesqApiKeyDTO);
            
            return $ret;
            
        } catch (Exception $e) {
            throw new InfraException('Erro ao listar chaves de API.', $e);
        }
    }

    /**
     * Conta chaves de API
     */
    public function contar(MdPesqApiKeyDTO $objMdPesqApiKeyDTO)
    {
        try {
            $this->validarContar($objMdPesqApiKeyDTO);
            
            $objMdPesqApiKeyBD = new MdPesqApiKeyBD($this->getObjInfraIBanco());
            $ret = $objMdPesqApiKeyBD->contar($objMdPesqApiKeyDTO);
            
            return $ret;
            
        } catch (Exception $e) {
            throw new InfraException('Erro ao contar chaves de API.', $e);
        }
    }

    /**
     * Validações para cadastro
     */
    private function validarCadastro(MdPesqApiKeyDTO $objMdPesqApiKeyDTO)
    {
        $this->validarNumIdContato($objMdPesqApiKeyDTO);
        $this->validarStrApiKey($objMdPesqApiKeyDTO);
        $this->validarStrSinAtivo($objMdPesqApiKeyDTO);
        
        // Verifica se a API key já existe
        $objConsulta = new MdPesqApiKeyDTO();
        $objConsulta->setStrApiKey($objMdPesqApiKeyDTO->getStrApiKey());
        $objConsulta->retNumIdApiKey();
        
        $objMdPesqApiKeyBD = new MdPesqApiKeyBD($this->getObjInfraIBanco());
        $objExistente = $objMdPesqApiKeyBD->consultar($objConsulta);
        
        if ($objExistente) {
            throw new InfraException('Chave de API já existe.');
        }
    }

    /**
     * Validações para alteração
     */
    private function validarAlteracao(MdPesqApiKeyDTO $objMdPesqApiKeyDTO)
    {
        $this->validarNumIdApiKey($objMdPesqApiKeyDTO);
        
        if ($objMdPesqApiKeyDTO->isSetNumIdContato()) {
            $this->validarNumIdContato($objMdPesqApiKeyDTO);
        }
        
        if ($objMdPesqApiKeyDTO->isSetStrApiKey()) {
            $this->validarStrApiKey($objMdPesqApiKeyDTO);
        }
        
        if ($objMdPesqApiKeyDTO->isSetStrSinAtivo()) {
            $this->validarStrSinAtivo($objMdPesqApiKeyDTO);
        }
    }

    /**
     * Validações para exclusão
     */
    private function validarExclusao($arrObjMdPesqApiKeyDTO)
    {
        if (!is_array($arrObjMdPesqApiKeyDTO)) {
            throw new InfraException('Parâmetro inválido.');
        }

        if (count($arrObjMdPesqApiKeyDTO) == 0) {
            throw new InfraException('Nenhuma chave de API informada.');
        }

        for ($i = 0; $i < count($arrObjMdPesqApiKeyDTO); $i++) {
            if (!($arrObjMdPesqApiKeyDTO[$i] instanceof MdPesqApiKeyDTO)) {
                throw new InfraException('Parâmetro inválido.');
            }
            $this->validarNumIdApiKey($arrObjMdPesqApiKeyDTO[$i]);
        }
    }

    /**
     * Validações para consulta
     */
    private function validarConsultar(MdPesqApiKeyDTO $objMdPesqApiKeyDTO)
    {
        if (!$objMdPesqApiKeyDTO->isSetNumIdApiKey() && !$objMdPesqApiKeyDTO->isSetStrApiKey()) {
            throw new InfraException('ID ou chave da API deve ser informado.');
        }
    }

    /**
     * Validações para listagem
     */
    private function validarListar(MdPesqApiKeyDTO $objMdPesqApiKeyDTO)
    {
        // Sem validações específicas para listagem
    }

    /**
     * Validações para contagem
     */
    private function validarContar(MdPesqApiKeyDTO $objMdPesqApiKeyDTO)
    {
        // Sem validações específicas para contagem
    }

    /**
     * Valida ID da API key
     */
    private function validarNumIdApiKey(MdPesqApiKeyDTO $objMdPesqApiKeyDTO)
    {
        if (!$objMdPesqApiKeyDTO->isSetNumIdApiKey()) {
            throw new InfraException('ID da chave de API não informado.');
        }
    }

    /**
     * Valida ID do contato
     */
    private function validarNumIdContato(MdPesqApiKeyDTO $objMdPesqApiKeyDTO)
    {
        if (!$objMdPesqApiKeyDTO->isSetNumIdContato()) {
            throw new InfraException('ID do contato não informado.');
        }
    }

    /**
     * Valida chave da API
     */
    private function validarStrApiKey(MdPesqApiKeyDTO $objMdPesqApiKeyDTO)
    {
        if (!$objMdPesqApiKeyDTO->isSetStrApiKey()) {
            throw new InfraException('Chave da API não informada.');
        }

        if (strlen($objMdPesqApiKeyDTO->getStrApiKey()) < 32) {
            throw new InfraException('Chave da API deve ter pelo menos 32 caracteres.');
        }
    }

    /**
     * Valida status ativo
     */
    private function validarStrSinAtivo(MdPesqApiKeyDTO $objMdPesqApiKeyDTO)
    {
        if (!$objMdPesqApiKeyDTO->isSetStrSinAtivo()) {
            throw new InfraException('Status ativo não informado.');
        }

        if (!in_array($objMdPesqApiKeyDTO->getStrSinAtivo(), array('S', 'N'))) {
            throw new InfraException('Status ativo deve ser S ou N.');
        }
    }
}