<?php

/**
 * DTO para gerenciamento de chaves de API
 */
class MdPesqApiKeyDTO extends InfraDTO
{
    public function getStrNomeTabela()
    {
        return 'md_pesq_api_key';
    }

    public function montarStrOrderBy()
    {
        return 'dth_criacao DESC';
    }

    public function getStrNomeSequencia()
    {
        return 'seq_md_pesq_api_key';
    }

    // ID da chave da API
    public function setNumIdApiKey($numIdApiKey)
    {
        $this->setNumero('id_api_key', $numIdApiKey);
    }

    public function getNumIdApiKey()
    {
        return $this->getNumero('id_api_key');
    }

    public function retNumIdApiKey()
    {
        return $this->retNumero('id_api_key');
    }

    // ID do contato/usuário
    public function setNumIdContato($numIdContato)
    {
        $this->setNumero('id_contato', $numIdContato);
    }

    public function getNumIdContato()
    {
        return $this->getNumero('id_contato');
    }

    public function retNumIdContato()
    {
        return $this->retNumero('id_contato');
    }

    // Chave da API
    public function setStrApiKey($strApiKey)
    {
        $this->setStr('api_key', $strApiKey);
    }

    public function getStrApiKey()
    {
        return $this->getStr('api_key');
    }

    public function retStrApiKey()
    {
        return $this->retStr('api_key');
    }

    // Status ativo/inativo
    public function setStrSinAtivo($strSinAtivo)
    {
        $this->setStr('sin_ativo', $strSinAtivo);
    }

    public function getStrSinAtivo()
    {
        return $this->getStr('sin_ativo');
    }

    public function retStrSinAtivo()
    {
        return $this->retStr('sin_ativo');
    }

    // Data de criação
    public function setDthCriacao($dthCriacao)
    {
        $this->setDataHora('dth_criacao', $dthCriacao);
    }

    public function getDthCriacao()
    {
        return $this->getDataHora('dth_criacao');
    }

    public function retDthCriacao()
    {
        return $this->retDataHora('dth_criacao');
    }

    // Data do último acesso
    public function setDthUltimoAcesso($dthUltimoAcesso)
    {
        $this->setDataHora('dth_ultimo_acesso', $dthUltimoAcesso);
    }

    public function getDthUltimoAcesso()
    {
        return $this->getDataHora('dth_ultimo_acesso');
    }

    public function retDthUltimoAcesso()
    {
        return $this->retDataHora('dth_ultimo_acesso');
    }

    // Data de expiração
    public function setDthExpiracao($dthExpiracao)
    {
        $this->setDataHora('dth_expiracao', $dthExpiracao);
    }

    public function getDthExpiracao()
    {
        return $this->getDataHora('dth_expiracao');
    }

    public function retDthExpiracao()
    {
        return $this->retDataHora('dth_expiracao');
    }

    // Descrição/nome da chave
    public function setStrDescricao($strDescricao)
    {
        $this->setStr('descricao', $strDescricao);
    }

    public function getStrDescricao()
    {
        return $this->getStr('descricao');
    }

    public function retStrDescricao()
    {
        return $this->retStr('descricao');
    }

    // Permissões da chave (JSON)
    public function setStrPermissoes($strPermissoes)
    {
        $this->setStr('permissoes', $strPermissoes);
    }

    public function getStrPermissoes()
    {
        return $this->getStr('permissoes');
    }

    public function retStrPermissoes()
    {
        return $this->retStr('permissoes');
    }
}