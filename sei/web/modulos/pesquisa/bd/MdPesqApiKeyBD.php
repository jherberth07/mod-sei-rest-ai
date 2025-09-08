<?php

/**
 * Classe de acesso ao banco de dados para chaves de API
 */
class MdPesqApiKeyBD extends InfraBD
{
    public function __construct(InfraIBanco $objInfraIBanco)
    {
        parent::__construct($objInfraIBanco);
    }

    /**
     * Cadastra uma nova chave de API
     */
    public function cadastrar(MdPesqApiKeyDTO $objMdPesqApiKeyDTO)
    {
        try {
            InfraDebug::getInstance()->gravar('CADASTRANDO CHAVE DE API');

            $objSequencialDTO = new SequencialDTO();
            $objSequencialDTO->setStrNome('SEQ_MD_PESQ_API_KEY');

            $objInfraSequencial = new InfraSequencial();
            $numId = $objInfraSequencial->gerar($objSequencialDTO);

            $objMdPesqApiKeyDTO->setNumIdApiKey($numId);

            $strSql = 'INSERT INTO md_pesq_api_key (' .
                     'id_api_key, id_contato, api_key, sin_ativo, dth_criacao, dth_ultimo_acesso, dth_expiracao, descricao, permissoes' .
                     ') VALUES (' .
                     $numId . ', ' .
                     $objMdPesqApiKeyDTO->getNumIdContato() . ', ' .
                     "'" . $this->formatarStrCriptografia($objMdPesqApiKeyDTO->getStrApiKey()) . "', " .
                     "'" . $objMdPesqApiKeyDTO->getStrSinAtivo() . "', " .
                     "'" . $this->formatarDataHoraBD($objMdPesqApiKeyDTO->getDthCriacao()) . "', " .
                     ($objMdPesqApiKeyDTO->isSetDthUltimoAcesso() ? "'" . $this->formatarDataHoraBD($objMdPesqApiKeyDTO->getDthUltimoAcesso()) . "'" : 'null') . ', ' .
                     ($objMdPesqApiKeyDTO->isSetDthExpiracao() ? "'" . $this->formatarDataHoraBD($objMdPesqApiKeyDTO->getDthExpiracao()) . "'" : 'null') . ', ' .
                     ($objMdPesqApiKeyDTO->isSetStrDescricao() ? "'" . $this->formatarStrCriptografia($objMdPesqApiKeyDTO->getStrDescricao()) . "'" : 'null') . ', ' .
                     ($objMdPesqApiKeyDTO->isSetStrPermissoes() ? "'" . $this->formatarStrCriptografia($objMdPesqApiKeyDTO->getStrPermissoes()) . "'" : 'null') .
                     ')';

            $this->executarSql($strSql);

            return $objMdPesqApiKeyDTO;

        } catch (Exception $e) {
            throw new InfraException('Erro cadastrando chave de API.', $e);
        }
    }

    /**
     * Altera uma chave de API
     */
    public function alterar(MdPesqApiKeyDTO $objMdPesqApiKeyDTO)
    {
        try {
            InfraDebug::getInstance()->gravar('ALTERANDO CHAVE DE API');

            $arrStrSet = array();

            if ($objMdPesqApiKeyDTO->isSetNumIdContato()) {
                $arrStrSet[] = 'id_contato = ' . $objMdPesqApiKeyDTO->getNumIdContato();
            }

            if ($objMdPesqApiKeyDTO->isSetStrApiKey()) {
                $arrStrSet[] = "api_key = '" . $this->formatarStrCriptografia($objMdPesqApiKeyDTO->getStrApiKey()) . "'";
            }

            if ($objMdPesqApiKeyDTO->isSetStrSinAtivo()) {
                $arrStrSet[] = "sin_ativo = '" . $objMdPesqApiKeyDTO->getStrSinAtivo() . "'";
            }

            if ($objMdPesqApiKeyDTO->isSetDthUltimoAcesso()) {
                $arrStrSet[] = "dth_ultimo_acesso = '" . $this->formatarDataHoraBD($objMdPesqApiKeyDTO->getDthUltimoAcesso()) . "'";
            }

            if ($objMdPesqApiKeyDTO->isSetDthExpiracao()) {
                $arrStrSet[] = "dth_expiracao = '" . $this->formatarDataHoraBD($objMdPesqApiKeyDTO->getDthExpiracao()) . "'";
            }

            if ($objMdPesqApiKeyDTO->isSetStrDescricao()) {
                $arrStrSet[] = "descricao = '" . $this->formatarStrCriptografia($objMdPesqApiKeyDTO->getStrDescricao()) . "'";
            }

            if ($objMdPesqApiKeyDTO->isSetStrPermissoes()) {
                $arrStrSet[] = "permissoes = '" . $this->formatarStrCriptografia($objMdPesqApiKeyDTO->getStrPermissoes()) . "'";
            }

            if (count($arrStrSet)) {
                $strSql = 'UPDATE md_pesq_api_key SET ' .
                         implode(', ', $arrStrSet) .
                         ' WHERE id_api_key = ' . $objMdPesqApiKeyDTO->getNumIdApiKey();

                $this->executarSql($strSql);
            }

        } catch (Exception $e) {
            throw new InfraException('Erro alterando chave de API.', $e);
        }
    }

    /**
     * Exclui uma chave de API
     */
    public function excluir(MdPesqApiKeyDTO $objMdPesqApiKeyDTO)
    {
        try {
            InfraDebug::getInstance()->gravar('EXCLUINDO CHAVE DE API');

            $strSql = 'DELETE FROM md_pesq_api_key WHERE id_api_key = ' . $objMdPesqApiKeyDTO->getNumIdApiKey();

            $this->executarSql($strSql);

        } catch (Exception $e) {
            throw new InfraException('Erro excluindo chave de API.', $e);
        }
    }

    /**
     * Consulta uma chave de API
     */
    public function consultar(MdPesqApiKeyDTO $objMdPesqApiKeyDTO)
    {
        try {
            InfraDebug::getInstance()->gravar('CONSULTANDO CHAVE DE API');

            $strSql = 'SELECT ' . $this->montarStrCampos($objMdPesqApiKeyDTO) .
                     ' FROM md_pesq_api_key ' .
                     ' WHERE 1=1 ';

            if ($objMdPesqApiKeyDTO->isSetNumIdApiKey()) {
                $strSql .= ' AND id_api_key = ' . $objMdPesqApiKeyDTO->getNumIdApiKey();
            }

            if ($objMdPesqApiKeyDTO->isSetStrApiKey()) {
                $strSql .= " AND api_key = '" . $this->formatarStrCriptografia($objMdPesqApiKeyDTO->getStrApiKey()) . "'";
            }

            if ($objMdPesqApiKeyDTO->isSetStrSinAtivo()) {
                $strSql .= " AND sin_ativo = '" . $objMdPesqApiKeyDTO->getStrSinAtivo() . "'";
            }

            $objResult = $this->executarSql($strSql);

            if ($objResult->getNumLinhas() > 1) {
                throw new InfraException('Chave de API não é única.');
            }

            $objMdPesqApiKeyDTO->setNumLinhas($objResult->getNumLinhas());

            if ($objResult->getNumLinhas() == 0) {
                return null;
            }

            return $this->montarObjeto($objResult, $objMdPesqApiKeyDTO);

        } catch (Exception $e) {
            throw new InfraException('Erro consultando chave de API.', $e);
        }
    }

    /**
     * Lista chaves de API
     */
    public function listar(MdPesqApiKeyDTO $objMdPesqApiKeyDTO)
    {
        try {
            InfraDebug::getInstance()->gravar('LISTANDO CHAVES DE API');

            $strSql = 'SELECT ' . $this->montarStrCampos($objMdPesqApiKeyDTO) .
                     ' FROM md_pesq_api_key ' .
                     ' WHERE 1=1 ';

            if ($objMdPesqApiKeyDTO->isSetNumIdApiKey()) {
                $strSql .= ' AND id_api_key = ' . $objMdPesqApiKeyDTO->getNumIdApiKey();
            }

            if ($objMdPesqApiKeyDTO->isSetNumIdContato()) {
                $strSql .= ' AND id_contato = ' . $objMdPesqApiKeyDTO->getNumIdContato();
            }

            if ($objMdPesqApiKeyDTO->isSetStrSinAtivo()) {
                $strSql .= " AND sin_ativo = '" . $objMdPesqApiKeyDTO->getStrSinAtivo() . "'";
            }

            $strSql .= ' ORDER BY ' . $objMdPesqApiKeyDTO->montarStrOrderBy();

            if ($objMdPesqApiKeyDTO->getNumMaxRegistrosRetorno() > 0) {
                $strSql = $this->limitarResultados($strSql, $objMdPesqApiKeyDTO->getNumMaxRegistrosRetorno());
            }

            $objResult = $this->executarSql($strSql);
            $objMdPesqApiKeyDTO->setNumLinhas($objResult->getNumLinhas());

            $arrObjMdPesqApiKeyDTO = array();

            while ($arrStrRegistro = $objResult->buscarLinha()) {
                $objMdPesqApiKeyDTOLocal = new MdPesqApiKeyDTO();
                $this->montarObjeto($objResult, $objMdPesqApiKeyDTOLocal, $arrStrRegistro);
                $arrObjMdPesqApiKeyDTO[] = $objMdPesqApiKeyDTOLocal;
            }

            return $arrObjMdPesqApiKeyDTO;

        } catch (Exception $e) {
            throw new InfraException('Erro listando chaves de API.', $e);
        }
    }

    /**
     * Conta chaves de API
     */
    public function contar(MdPesqApiKeyDTO $objMdPesqApiKeyDTO)
    {
        try {
            InfraDebug::getInstance()->gravar('CONTANDO CHAVES DE API');

            $strSql = 'SELECT COUNT(*) FROM md_pesq_api_key WHERE 1=1 ';

            if ($objMdPesqApiKeyDTO->isSetNumIdContato()) {
                $strSql .= ' AND id_contato = ' . $objMdPesqApiKeyDTO->getNumIdContato();
            }

            if ($objMdPesqApiKeyDTO->isSetStrSinAtivo()) {
                $strSql .= " AND sin_ativo = '" . $objMdPesqApiKeyDTO->getStrSinAtivo() . "'";
            }

            $objResult = $this->executarSql($strSql);

            return $objResult->buscarValor();

        } catch (Exception $e) {
            throw new InfraException('Erro contando chaves de API.', $e);
        }
    }

    /**
     * Monta string dos campos para consulta
     */
    private function montarStrCampos(MdPesqApiKeyDTO $objMdPesqApiKeyDTO)
    {
        $arrStrCampos = array();

        if ($objMdPesqApiKeyDTO->isRetNumIdApiKey()) {
            $arrStrCampos[] = 'id_api_key';
        }

        if ($objMdPesqApiKeyDTO->isRetNumIdContato()) {
            $arrStrCampos[] = 'id_contato';
        }

        if ($objMdPesqApiKeyDTO->isRetStrApiKey()) {
            $arrStrCampos[] = 'api_key';
        }

        if ($objMdPesqApiKeyDTO->isRetStrSinAtivo()) {
            $arrStrCampos[] = 'sin_ativo';
        }

        if ($objMdPesqApiKeyDTO->isRetDthCriacao()) {
            $arrStrCampos[] = 'dth_criacao';
        }

        if ($objMdPesqApiKeyDTO->isRetDthUltimoAcesso()) {
            $arrStrCampos[] = 'dth_ultimo_acesso';
        }

        if ($objMdPesqApiKeyDTO->isRetDthExpiracao()) {
            $arrStrCampos[] = 'dth_expiracao';
        }

        if ($objMdPesqApiKeyDTO->isRetStrDescricao()) {
            $arrStrCampos[] = 'descricao';
        }

        if ($objMdPesqApiKeyDTO->isRetStrPermissoes()) {
            $arrStrCampos[] = 'permissoes';
        }

        return implode(', ', $arrStrCampos);
    }

    /**
     * Monta objeto a partir do resultado da consulta
     */
    private function montarObjeto(InfraResult $objResult, MdPesqApiKeyDTO $objMdPesqApiKeyDTO, $arrStrRegistro = null)
    {
        if ($arrStrRegistro == null) {
            $arrStrRegistro = $objResult->buscarLinha();
        }

        $objMdPesqApiKeyDTO->setNumLinhas($objResult->getNumLinhas());

        if ($objMdPesqApiKeyDTO->isRetNumIdApiKey()) {
            $objMdPesqApiKeyDTO->setNumIdApiKey($arrStrRegistro['id_api_key']);
        }

        if ($objMdPesqApiKeyDTO->isRetNumIdContato()) {
            $objMdPesqApiKeyDTO->setNumIdContato($arrStrRegistro['id_contato']);
        }

        if ($objMdPesqApiKeyDTO->isRetStrApiKey()) {
            $objMdPesqApiKeyDTO->setStrApiKey($this->reconverterStrCriptografia($arrStrRegistro['api_key']));
        }

        if ($objMdPesqApiKeyDTO->isRetStrSinAtivo()) {
            $objMdPesqApiKeyDTO->setStrSinAtivo($arrStrRegistro['sin_ativo']);
        }

        if ($objMdPesqApiKeyDTO->isRetDthCriacao()) {
            $objMdPesqApiKeyDTO->setDthCriacao($this->reconverterDataHoraBD($arrStrRegistro['dth_criacao']));
        }

        if ($objMdPesqApiKeyDTO->isRetDthUltimoAcesso()) {
            $objMdPesqApiKeyDTO->setDthUltimoAcesso($this->reconverterDataHoraBD($arrStrRegistro['dth_ultimo_acesso']));
        }

        if ($objMdPesqApiKeyDTO->isRetDthExpiracao()) {
            $objMdPesqApiKeyDTO->setDthExpiracao($this->reconverterDataHoraBD($arrStrRegistro['dth_expiracao']));
        }

        if ($objMdPesqApiKeyDTO->isRetStrDescricao()) {
            $objMdPesqApiKeyDTO->setStrDescricao($this->reconverterStrCriptografia($arrStrRegistro['descricao']));
        }

        if ($objMdPesqApiKeyDTO->isRetStrPermissoes()) {
            $objMdPesqApiKeyDTO->setStrPermissoes($this->reconverterStrCriptografia($arrStrRegistro['permissoes']));
        }

        return $objMdPesqApiKeyDTO;
    }
}