<?php

/**
 * Classe de acesso ao banco de dados para logs de API
 */
class MdPesqApiLogBD extends InfraBD
{
    public function __construct(InfraIBanco $objInfraIBanco)
    {
        parent::__construct($objInfraIBanco);
    }

    /**
     * Cadastra um novo log de API
     */
    public function cadastrar(MdPesqApiLogDTO $objMdPesqApiLogDTO)
    {
        try {
            InfraDebug::getInstance()->gravar('CADASTRANDO LOG DE API');

            $objSequencialDTO = new SequencialDTO();
            $objSequencialDTO->setStrNome('SEQ_MD_PESQ_API_LOG');

            $objInfraSequencial = new InfraSequencial();
            $numId = $objInfraSequencial->gerar($objSequencialDTO);

            $objMdPesqApiLogDTO->setNumIdLog($numId);

            $strSql = 'INSERT INTO md_pesq_api_log (' .
                     'id_log, id_api_key, endpoint, metodo, parametros, dth_acesso, ip_usuario, user_agent, codigo_resposta, tempo_execucao' .
                     ') VALUES (' .
                     $numId . ', ' .
                     ($objMdPesqApiLogDTO->isSetNumIdApiKey() ? $objMdPesqApiLogDTO->getNumIdApiKey() : 'null') . ', ' .
                     "'" . $this->formatarStrCriptografia($objMdPesqApiLogDTO->getStrEndpoint()) . "', " .
                     "'" . $this->formatarStrCriptografia($objMdPesqApiLogDTO->getStrMetodo()) . "', " .
                     ($objMdPesqApiLogDTO->isSetStrParametros() ? "'" . $this->formatarStrCriptografia($objMdPesqApiLogDTO->getStrParametros()) . "'" : 'null') . ', ' .
                     "'" . $this->formatarDataHoraBD($objMdPesqApiLogDTO->getDthAcesso()) . "', " .
                     ($objMdPesqApiLogDTO->isSetStrIpUsuario() ? "'" . $this->formatarStrCriptografia($objMdPesqApiLogDTO->getStrIpUsuario()) . "'" : 'null') . ', ' .
                     ($objMdPesqApiLogDTO->isSetStrUserAgent() ? "'" . $this->formatarStrCriptografia($objMdPesqApiLogDTO->getStrUserAgent()) . "'" : 'null') . ', ' .
                     ($objMdPesqApiLogDTO->isSetNumCodigoResposta() ? $objMdPesqApiLogDTO->getNumCodigoResposta() : 'null') . ', ' .
                     ($objMdPesqApiLogDTO->isSetNumTempoExecucao() ? $objMdPesqApiLogDTO->getNumTempoExecucao() : 'null') .
                     ')';

            $this->executarSql($strSql);

            return $objMdPesqApiLogDTO;

        } catch (Exception $e) {
            throw new InfraException('Erro cadastrando log de API.', $e);
        }
    }

    /**
     * Exclui um log de API
     */
    public function excluir(MdPesqApiLogDTO $objMdPesqApiLogDTO)
    {
        try {
            InfraDebug::getInstance()->gravar('EXCLUINDO LOG DE API');

            $strSql = 'DELETE FROM md_pesq_api_log WHERE id_log = ' . $objMdPesqApiLogDTO->getNumIdLog();

            $this->executarSql($strSql);

        } catch (Exception $e) {
            throw new InfraException('Erro excluindo log de API.', $e);
        }
    }

    /**
     * Lista logs de API
     */
    public function listar(MdPesqApiLogDTO $objMdPesqApiLogDTO)
    {
        try {
            InfraDebug::getInstance()->gravar('LISTANDO LOGS DE API');

            $strSql = 'SELECT ' . $this->montarStrCampos($objMdPesqApiLogDTO) .
                     ' FROM md_pesq_api_log ' .
                     ' WHERE 1=1 ';

            if ($objMdPesqApiLogDTO->isSetNumIdLog()) {
                $strSql .= ' AND id_log = ' . $objMdPesqApiLogDTO->getNumIdLog();
            }

            if ($objMdPesqApiLogDTO->isSetNumIdApiKey()) {
                $strSql .= ' AND id_api_key = ' . $objMdPesqApiLogDTO->getNumIdApiKey();
            }

            if ($objMdPesqApiLogDTO->isSetStrEndpoint()) {
                $strSql .= " AND endpoint = '" . $this->formatarStrCriptografia($objMdPesqApiLogDTO->getStrEndpoint()) . "'";
            }

            if ($objMdPesqApiLogDTO->isSetStrMetodo()) {
                $strSql .= " AND metodo = '" . $this->formatarStrCriptografia($objMdPesqApiLogDTO->getStrMetodo()) . "'";
            }

            if ($objMdPesqApiLogDTO->isSetDthAcesso()) {
                $strSql .= $this->processarCriterioDataHora('dth_acesso', $objMdPesqApiLogDTO->getDthAcesso(), $objMdPesqApiLogDTO->getObjInfraMetodo('dth_acesso'));
            }

            if ($objMdPesqApiLogDTO->isSetStrIpUsuario()) {
                $strSql .= " AND ip_usuario = '" . $this->formatarStrCriptografia($objMdPesqApiLogDTO->getStrIpUsuario()) . "'";
            }

            if ($objMdPesqApiLogDTO->isSetNumCodigoResposta()) {
                $strSql .= ' AND codigo_resposta = ' . $objMdPesqApiLogDTO->getNumCodigoResposta();
            }

            $strSql .= ' ORDER BY ' . $objMdPesqApiLogDTO->montarStrOrderBy();

            if ($objMdPesqApiLogDTO->getNumMaxRegistrosRetorno() > 0) {
                $strSql = $this->limitarResultados($strSql, $objMdPesqApiLogDTO->getNumMaxRegistrosRetorno());
            }

            $objResult = $this->executarSql($strSql);
            $objMdPesqApiLogDTO->setNumLinhas($objResult->getNumLinhas());

            $arrObjMdPesqApiLogDTO = array();

            while ($arrStrRegistro = $objResult->buscarLinha()) {
                $objMdPesqApiLogDTOLocal = new MdPesqApiLogDTO();
                $this->montarObjeto($objResult, $objMdPesqApiLogDTOLocal, $arrStrRegistro);
                $arrObjMdPesqApiLogDTO[] = $objMdPesqApiLogDTOLocal;
            }

            return $arrObjMdPesqApiLogDTO;

        } catch (Exception $e) {
            throw new InfraException('Erro listando logs de API.', $e);
        }
    }

    /**
     * Conta logs de API
     */
    public function contar(MdPesqApiLogDTO $objMdPesqApiLogDTO)
    {
        try {
            InfraDebug::getInstance()->gravar('CONTANDO LOGS DE API');

            $strSql = 'SELECT COUNT(*) FROM md_pesq_api_log WHERE 1=1 ';

            if ($objMdPesqApiLogDTO->isSetNumIdApiKey()) {
                $strSql .= ' AND id_api_key = ' . $objMdPesqApiLogDTO->getNumIdApiKey();
            }

            if ($objMdPesqApiLogDTO->isSetStrEndpoint()) {
                $strSql .= " AND endpoint = '" . $this->formatarStrCriptografia($objMdPesqApiLogDTO->getStrEndpoint()) . "'";
            }

            if ($objMdPesqApiLogDTO->isSetStrMetodo()) {
                $strSql .= " AND metodo = '" . $this->formatarStrCriptografia($objMdPesqApiLogDTO->getStrMetodo()) . "'";
            }

            if ($objMdPesqApiLogDTO->isSetDthAcesso()) {
                $strSql .= $this->processarCriterioDataHora('dth_acesso', $objMdPesqApiLogDTO->getDthAcesso(), $objMdPesqApiLogDTO->getObjInfraMetodo('dth_acesso'));
            }

            if ($objMdPesqApiLogDTO->isSetStrIpUsuario()) {
                $strSql .= " AND ip_usuario = '" . $this->formatarStrCriptografia($objMdPesqApiLogDTO->getStrIpUsuario()) . "'";
            }

            if ($objMdPesqApiLogDTO->isSetNumCodigoResposta()) {
                $strSql .= ' AND codigo_resposta = ' . $objMdPesqApiLogDTO->getNumCodigoResposta();
            }

            $objResult = $this->executarSql($strSql);

            return $objResult->buscarValor();

        } catch (Exception $e) {
            throw new InfraException('Erro contando logs de API.', $e);
        }
    }

    /**
     * Monta string dos campos para consulta
     */
    private function montarStrCampos(MdPesqApiLogDTO $objMdPesqApiLogDTO)
    {
        $arrStrCampos = array();

        if ($objMdPesqApiLogDTO->isRetNumIdLog()) {
            $arrStrCampos[] = 'id_log';
        }

        if ($objMdPesqApiLogDTO->isRetNumIdApiKey()) {
            $arrStrCampos[] = 'id_api_key';
        }

        if ($objMdPesqApiLogDTO->isRetStrEndpoint()) {
            $arrStrCampos[] = 'endpoint';
        }

        if ($objMdPesqApiLogDTO->isRetStrMetodo()) {
            $arrStrCampos[] = 'metodo';
        }

        if ($objMdPesqApiLogDTO->isRetStrParametros()) {
            $arrStrCampos[] = 'parametros';
        }

        if ($objMdPesqApiLogDTO->isRetDthAcesso()) {
            $arrStrCampos[] = 'dth_acesso';
        }

        if ($objMdPesqApiLogDTO->isRetStrIpUsuario()) {
            $arrStrCampos[] = 'ip_usuario';
        }

        if ($objMdPesqApiLogDTO->isRetStrUserAgent()) {
            $arrStrCampos[] = 'user_agent';
        }

        if ($objMdPesqApiLogDTO->isRetNumCodigoResposta()) {
            $arrStrCampos[] = 'codigo_resposta';
        }

        if ($objMdPesqApiLogDTO->isRetNumTempoExecucao()) {
            $arrStrCampos[] = 'tempo_execucao';
        }

        return implode(', ', $arrStrCampos);
    }

    /**
     * Monta objeto a partir do resultado da consulta
     */
    private function montarObjeto(InfraResult $objResult, MdPesqApiLogDTO $objMdPesqApiLogDTO, $arrStrRegistro = null)
    {
        if ($arrStrRegistro == null) {
            $arrStrRegistro = $objResult->buscarLinha();
        }

        $objMdPesqApiLogDTO->setNumLinhas($objResult->getNumLinhas());

        if ($objMdPesqApiLogDTO->isRetNumIdLog()) {
            $objMdPesqApiLogDTO->setNumIdLog($arrStrRegistro['id_log']);
        }

        if ($objMdPesqApiLogDTO->isRetNumIdApiKey()) {
            $objMdPesqApiLogDTO->setNumIdApiKey($arrStrRegistro['id_api_key']);
        }

        if ($objMdPesqApiLogDTO->isRetStrEndpoint()) {
            $objMdPesqApiLogDTO->setStrEndpoint($this->reconverterStrCriptografia($arrStrRegistro['endpoint']));
        }

        if ($objMdPesqApiLogDTO->isRetStrMetodo()) {
            $objMdPesqApiLogDTO->setStrMetodo($this->reconverterStrCriptografia($arrStrRegistro['metodo']));
        }

        if ($objMdPesqApiLogDTO->isRetStrParametros()) {
            $objMdPesqApiLogDTO->setStrParametros($this->reconverterStrCriptografia($arrStrRegistro['parametros']));
        }

        if ($objMdPesqApiLogDTO->isRetDthAcesso()) {
            $objMdPesqApiLogDTO->setDthAcesso($this->reconverterDataHoraBD($arrStrRegistro['dth_acesso']));
        }

        if ($objMdPesqApiLogDTO->isRetStrIpUsuario()) {
            $objMdPesqApiLogDTO->setStrIpUsuario($this->reconverterStrCriptografia($arrStrRegistro['ip_usuario']));
        }

        if ($objMdPesqApiLogDTO->isRetStrUserAgent()) {
            $objMdPesqApiLogDTO->setStrUserAgent($this->reconverterStrCriptografia($arrStrRegistro['user_agent']));
        }

        if ($objMdPesqApiLogDTO->isRetNumCodigoResposta()) {
            $objMdPesqApiLogDTO->setNumCodigoResposta($arrStrRegistro['codigo_resposta']);
        }

        if ($objMdPesqApiLogDTO->isRetNumTempoExecucao()) {
            $objMdPesqApiLogDTO->setNumTempoExecucao($arrStrRegistro['tempo_execucao']);
        }

        return $objMdPesqApiLogDTO;
    }

    /**
     * Processa critério de data/hora com operadores
     */
    private function processarCriterioDataHora($campo, $valor, $objInfraMetodo)
    {
        if ($objInfraMetodo) {
            $operador = $objInfraMetodo->getStrOperador();
            switch ($operador) {
                case InfraDTO::$OPER_MAIOR:
                    return " AND {$campo} > '" . $this->formatarDataHoraBD($valor) . "'";
                case InfraDTO::$OPER_MAIOR_IGUAL:
                    return " AND {$campo} >= '" . $this->formatarDataHoraBD($valor) . "'";
                case InfraDTO::$OPER_MENOR:
                    return " AND {$campo} < '" . $this->formatarDataHoraBD($valor) . "'";
                case InfraDTO::$OPER_MENOR_IGUAL:
                    return " AND {$campo} <= '" . $this->formatarDataHoraBD($valor) . "'";
                default:
                    return " AND {$campo} = '" . $this->formatarDataHoraBD($valor) . "'";
            }
        }
        
        return " AND {$campo} = '" . $this->formatarDataHoraBD($valor) . "'";
    }
}