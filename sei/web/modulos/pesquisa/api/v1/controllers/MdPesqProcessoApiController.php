<?php

/**
 * Controlador de processos da API REST v1
 */
class MdPesqProcessoApiController extends MdPesqApiController
{
    /**
     * Lista processos públicos
     * GET /api/v1/processes
     */
    public function listar($parametros = array())
    {
        try {
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
            $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
            $orgaoId = isset($_GET['orgao_id']) ? (int)$_GET['orgao_id'] : null;
            
            // Valida limites
            if ($limit > 100) $limit = 100;
            if ($limit < 1) $limit = 20;
            if ($offset < 0) $offset = 0;
            
            $objPesquisaProtocoloDTO = new PesquisaProtocoloDTO();
            $objPesquisaProtocoloDTO->setStrStaTipo(ProtocoloRN::$TPP_PROCEDIMENTOS);
            $objPesquisaProtocoloDTO->setStrStaAcesso(ProtocoloRN::$NA_PUBLICO);
            $objPesquisaProtocoloDTO->setNumMaxRegistrosRetorno($limit);
            
            if ($orgaoId) {
                $objPesquisaProtocoloDTO->setNumIdOrgaoUnidadeGeradora($orgaoId);
            }
            
            $objProtocoloRN = new ProtocoloRN();
            $arrProcessos = $objProtocoloRN->pesquisarRN0967($objPesquisaProtocoloDTO);
            
            $resultados = array();
            $count = 0;
            
            foreach ($arrProcessos as $processo) {
                if ($count < $offset) {
                    $count++;
                    continue;
                }
                
                if (count($resultados) >= $limit) {
                    break;
                }
                
                $resultados[] = $this->formatarProcesso($processo);
                $count++;
            }
            
            $dados = array(
                'processes' => $resultados,
                'total' => count($arrProcessos),
                'limit' => $limit,
                'offset' => $offset,
                'returned' => count($resultados)
            );
            
            $this->retornarSucesso($dados, 'Processos listados com sucesso');
            
        } catch (Exception $e) {
            $this->retornarErro(500, 'Erro ao listar processos', 'LIST_ERROR', $e->getMessage());
        }
    }
    
    /**
     * Obtém um processo específico
     * GET /api/v1/processes/{id}
     */
    public function obter($parametros = array())
    {
        try {
            if (!isset($parametros['id'])) {
                $this->retornarErro(400, 'ID do processo é obrigatório', 'MISSING_PROCESS_ID');
                return;
            }
            
            $idProcesso = $parametros['id'];
            
            $objProcedimentoDTO = new ProcedimentoDTO();
            $objProcedimentoDTO->setDblIdProcedimento($idProcesso);
            $objProcedimentoDTO->retDblIdProcedimento();
            $objProcedimentoDTO->retStrProtocoloProcedimentoFormatado();
            $objProcedimentoDTO->retStrNomeTipoProcedimento();
            $objProcedimentoDTO->retDthAbertura();
            $objProcedimentoDTO->retStrStaNivelAcessoLocalProtocolo();
            $objProcedimentoDTO->retStrStaNivelAcessoGlobalProtocolo();
            $objProcedimentoDTO->retNumIdUnidadeGeradora();
            $objProcedimentoDTO->retStrSiglaUnidadeGeradora();
            $objProcedimentoDTO->retStrDescricaoUnidadeGeradora();
            $objProcedimentoDTO->retNumIdOrgaoUnidadeGeradora();
            $objProcedimentoDTO->retStrSiglaOrgaoUnidadeGeradora();
            $objProcedimentoDTO->retStrDescricaoOrgaoUnidadeGeradora();
            $objProcedimentoDTO->retStrStaEstadoProtocolo();
            $objProcedimentoDTO->retStrEspecificacao();
            
            $objProcedimentoRN = new ProcedimentoRN();
            $objProcesso = $objProcedimentoRN->consultarRN0201($objProcedimentoDTO);
            
            if (!$objProcesso) {
                $this->retornarErro(404, 'Processo não encontrado', 'PROCESS_NOT_FOUND');
                return;
            }
            
            // Verifica se o processo é público
            if ($objProcesso->getStrStaNivelAcessoLocalProtocolo() !== ProtocoloRN::$NA_PUBLICO) {
                $this->retornarErro(403, 'Acesso negado ao processo', 'ACCESS_DENIED');
                return;
            }
            
            $dadosProcesso = $this->formatarProcessoDetalhado($objProcesso);
            
            $this->retornarSucesso($dadosProcesso, 'Processo obtido com sucesso');
            
        } catch (Exception $e) {
            $this->retornarErro(500, 'Erro ao obter processo', 'GET_ERROR', $e->getMessage());
        }
    }
    
    /**
     * Lista documentos de um processo
     * GET /api/v1/processes/{id}/documents
     */
    public function listarDocumentos($parametros = array())
    {
        try {
            if (!isset($parametros['id'])) {
                $this->retornarErro(400, 'ID do processo é obrigatório', 'MISSING_PROCESS_ID');
                return;
            }
            
            $idProcesso = $parametros['id'];
            
            // Verifica se o processo existe e é público
            $objProcedimentoDTO = new ProcedimentoDTO();
            $objProcedimentoDTO->setDblIdProcedimento($idProcesso);
            $objProcedimentoDTO->retStrStaNivelAcessoLocalProtocolo();
            
            $objProcedimentoRN = new ProcedimentoRN();
            $objProcesso = $objProcedimentoRN->consultarRN0201($objProcedimentoDTO);
            
            if (!$objProcesso || $objProcesso->getStrStaNivelAcessoLocalProtocolo() !== ProtocoloRN::$NA_PUBLICO) {
                $this->retornarErro(403, 'Acesso negado ao processo', 'ACCESS_DENIED');
                return;
            }
            
            // Lista documentos públicos do processo
            $objDocumentoDTO = new DocumentoDTO();
            $objDocumentoDTO->setDblIdProcedimento($idProcesso);
            $objDocumentoDTO->setStrStaNivelAcessoLocalProtocolo(ProtocoloRN::$NA_PUBLICO);
            $objDocumentoDTO->retDblIdDocumento();
            $objDocumentoDTO->retStrProtocoloDocumentoFormatado();
            $objDocumentoDTO->retStrNomeSerie();
            $objDocumentoDTO->retStrNumero();
            $objDocumentoDTO->retDthGeracao();
            $objDocumentoDTO->retStrStaDocumento();
            $objDocumentoDTO->retNumIdSerie();
            $objDocumentoDTO->retStrStaNivelAcessoLocalProtocolo();
            
            $objDocumentoRN = new DocumentoRN();
            $arrDocumentos = $objDocumentoRN->listarRN0008($objDocumentoDTO);
            
            $resultados = array();
            foreach ($arrDocumentos as $documento) {
                $resultados[] = $this->formatarDocumento($documento);
            }
            
            $dados = array(
                'process_id' => $idProcesso,
                'documents' => $resultados,
                'total' => count($resultados)
            );
            
            $this->retornarSucesso($dados, 'Documentos listados com sucesso');
            
        } catch (Exception $e) {
            $this->retornarErro(500, 'Erro ao listar documentos', 'LIST_DOCUMENTS_ERROR', $e->getMessage());
        }
    }
    
    /**
     * Pesquisa processos
     * GET /api/v1/processes/search
     */
    public function pesquisar($parametros = array())
    {
        try {
            $termo = isset($_GET['q']) ? trim($_GET['q']) : '';
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
            $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
            
            if (empty($termo)) {
                $this->retornarErro(400, 'Termo de pesquisa é obrigatório', 'MISSING_SEARCH_TERM');
                return;
            }
            
            if (strlen($termo) < 3) {
                $this->retornarErro(400, 'Termo de pesquisa deve ter pelo menos 3 caracteres', 'SEARCH_TERM_TOO_SHORT');
                return;
            }
            
            // Valida limites
            if ($limit > 100) $limit = 100;
            if ($limit < 1) $limit = 20;
            if ($offset < 0) $offset = 0;
            
            // Usa a funcionalidade existente de pesquisa
            $objMdPesqBuscaProtocoloExterno = new MdPesqBuscaProtocoloExterno();
            
            // Prepara parâmetros de pesquisa
            $arrParams = array(
                'palavras_pesquisa' => $termo,
                'tipo_pesquisa' => 'processo',
                'nivel_acesso' => ProtocoloRN::$NA_PUBLICO
            );
            
            $arrResultados = $objMdPesqBuscaProtocoloExterno->pesquisarProcessos($arrParams);
            
            $resultados = array();
            $count = 0;
            
            foreach ($arrResultados as $resultado) {
                if ($count < $offset) {
                    $count++;
                    continue;
                }
                
                if (count($resultados) >= $limit) {
                    break;
                }
                
                $resultados[] = $this->formatarResultadoPesquisa($resultado);
                $count++;
            }
            
            $dados = array(
                'query' => $termo,
                'results' => $resultados,
                'total' => count($arrResultados),
                'limit' => $limit,
                'offset' => $offset,
                'returned' => count($resultados)
            );
            
            $this->retornarSucesso($dados, 'Pesquisa realizada com sucesso');
            
        } catch (Exception $e) {
            $this->retornarErro(500, 'Erro ao pesquisar processos', 'SEARCH_ERROR', $e->getMessage());
        }
    }
    
    /**
     * Formata dados do processo para resposta da API
     */
    private function formatarProcesso($processo)
    {
        return array(
            'id' => $processo->getDblIdProtocolo(),
            'protocol' => $processo->getStrProtocoloFormatado(),
            'type' => $processo->getStrNomeTipoProcedimento(),
            'opening_date' => $processo->getDthAbertura(),
            'status' => $processo->getStrStaEstadoProtocolo(),
            'generating_unit' => array(
                'id' => $processo->getNumIdUnidadeGeradora(),
                'acronym' => $processo->getStrSiglaUnidadeGeradora(),
                'description' => $processo->getStrDescricaoUnidadeGeradora()
            ),
            'organ' => array(
                'id' => $processo->getNumIdOrgaoUnidadeGeradora(),
                'acronym' => $processo->getStrSiglaOrgaoUnidadeGeradora(),
                'description' => $processo->getStrDescricaoOrgaoUnidadeGeradora()
            )
        );
    }
    
    /**
     * Formata dados detalhados do processo
     */
    private function formatarProcessoDetalhado($processo)
    {
        $dados = $this->formatarProcesso($processo);
        
        $dados['specification'] = $processo->getStrEspecificacao();
        $dados['access_level'] = $processo->getStrStaNivelAcessoLocalProtocolo();
        $dados['global_access_level'] = $processo->getStrStaNivelAcessoGlobalProtocolo();
        
        return $dados;
    }
    
    /**
     * Formata dados do documento
     */
    private function formatarDocumento($documento)
    {
        return array(
            'id' => $documento->getDblIdDocumento(),
            'protocol' => $documento->getStrProtocoloDocumentoFormatado(),
            'series' => $documento->getStrNomeSerie(),
            'number' => $documento->getStrNumero(),
            'generation_date' => $documento->getDthGeracao(),
            'status' => $documento->getStrStaDocumento(),
            'access_level' => $documento->getStrStaNivelAcessoLocalProtocolo()
        );
    }
    
    /**
     * Formata resultado da pesquisa
     */
    private function formatarResultadoPesquisa($resultado)
    {
        // Esta implementação depende da estrutura retornada pelo MdPesqBuscaProtocoloExterno
        // Adapte conforme necessário
        return array(
            'id' => isset($resultado['id']) ? $resultado['id'] : null,
            'protocol' => isset($resultado['protocolo']) ? $resultado['protocolo'] : null,
            'type' => isset($resultado['tipo']) ? $resultado['tipo'] : null,
            'relevance' => isset($resultado['relevancia']) ? $resultado['relevancia'] : null
        );
    }
}