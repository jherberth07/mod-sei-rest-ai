<?php

/**
 * DTO para log de uso da API
 */
class MdPesqApiLogDTO extends InfraDTO
{
    public function getStrNomeTabela()
    {
        return 'md_pesq_api_log';
    }

    public function montarStrOrderBy()
    {
        return 'dth_acesso DESC';
    }

    public function getStrNomeSequencia()
    {
        return 'seq_md_pesq_api_log';
    }

    // ID do log
    public function setNumIdLog($numIdLog)
    {
        $this->setNumero('id_log', $numIdLog);
    }

    public function getNumIdLog()
    {
        return $this->getNumero('id_log');
    }

    public function retNumIdLog()
    {
        return $this->retNumero('id_log');
    }

    // ID da chave da API (opcional)
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

    // Endpoint acessado
    public function setStrEndpoint($strEndpoint)
    {
        $this->setStr('endpoint', $strEndpoint);
    }

    public function getStrEndpoint()
    {
        return $this->getStr('endpoint');
    }

    public function retStrEndpoint()
    {
        return $this->retStr('endpoint');
    }

    // Método HTTP
    public function setStrMetodo($strMetodo)
    {
        $this->setStr('metodo', $strMetodo);
    }

    public function getStrMetodo()
    {
        return $this->getStr('metodo');
    }

    public function retStrMetodo()
    {
        return $this->retStr('metodo');
    }

    // Parâmetros da requisição (JSON)
    public function setStrParametros($strParametros)
    {
        $this->setStr('parametros', $strParametros);
    }

    public function getStrParametros()
    {
        return $this->getStr('parametros');
    }

    public function retStrParametros()
    {
        return $this->retStr('parametros');
    }

    // Data/hora do acesso
    public function setDthAcesso($dthAcesso)
    {
        $this->setDataHora('dth_acesso', $dthAcesso);
    }

    public function getDthAcesso()
    {
        return $this->getDataHora('dth_acesso');
    }

    public function retDthAcesso()
    {
        return $this->retDataHora('dth_acesso');
    }

    // IP do usuário
    public function setStrIpUsuario($strIpUsuario)
    {
        $this->setStr('ip_usuario', $strIpUsuario);
    }

    public function getStrIpUsuario()
    {
        return $this->getStr('ip_usuario');
    }

    public function retStrIpUsuario()
    {
        return $this->retStr('ip_usuario');
    }

    // User Agent
    public function setStrUserAgent($strUserAgent)
    {
        $this->setStr('user_agent', $strUserAgent);
    }

    public function getStrUserAgent()
    {
        return $this->getStr('user_agent');
    }

    public function retStrUserAgent()
    {
        return $this->retStr('user_agent');
    }

    // Código de resposta HTTP
    public function setNumCodigoResposta($numCodigoResposta)
    {
        $this->setNumero('codigo_resposta', $numCodigoResposta);
    }

    public function getNumCodigoResposta()
    {
        return $this->getNumero('codigo_resposta');
    }

    public function retNumCodigoResposta()
    {
        return $this->retNumero('codigo_resposta');
    }

    // Tempo de execução (ms)
    public function setNumTempoExecucao($numTempoExecucao)
    {
        $this->setNumero('tempo_execucao', $numTempoExecucao);
    }

    public function getNumTempoExecucao()
    {
        return $this->getNumero('tempo_execucao');
    }

    public function retNumTempoExecucao()
    {
        return $this->retNumero('tempo_execucao');
    }
}