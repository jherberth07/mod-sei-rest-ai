<?php

/**
 * Controlador de documentos da API REST v1
 */
class MdPesqDocumentoApiController extends MdPesqApiController
{
    /**
     * Obtém um documento específico
     * GET /api/v1/documents/{id}
     */
    public function obter($parametros = array())
    {
        try {
            if (!isset($parametros['id'])) {
                $this->retornarErro(400, 'ID do documento é obrigatório', 'MISSING_DOCUMENT_ID');
                return;
            }
            
            $idDocumento = $parametros['id'];
            
            $objDocumentoDTO = new DocumentoDTO();
            $objDocumentoDTO->setDblIdDocumento($idDocumento);
            $objDocumentoDTO->retDblIdDocumento();
            $objDocumentoDTO->retStrProtocoloDocumentoFormatado();
            $objDocumentoDTO->retStrNomeSerie();
            $objDocumentoDTO->retStrNumero();
            $objDocumentoDTO->retDthGeracao();
            $objDocumentoDTO->retStrStaDocumento();
            $objDocumentoDTO->retStrStaNivelAcessoLocalProtocolo();
            $objDocumentoDTO->retStrStaNivelAcessoGlobalProtocolo();
            $objDocumentoDTO->retDblIdProcedimento();
            $objDocumentoDTO->retNumIdUnidadeGeradoraProtocolo();
            $objDocumentoDTO->retStrSiglaUnidadeGeradoraProtocolo();
            $objDocumentoDTO->retStrDescricaoUnidadeGeradoraProtocolo();
            $objDocumentoDTO->retNumIdOrgaoUnidadeGeradoraProtocolo();
            $objDocumentoDTO->retStrSiglaOrgaoUnidadeGeradoraProtocolo();
            $objDocumentoDTO->retStrDescricaoOrgaoUnidadeGeradoraProtocolo();
            $objDocumentoDTO->retNumIdSerie();
            $objDocumentoDTO->retStrObservacao();
            
            $objDocumentoRN = new DocumentoRN();
            $objDocumento = $objDocumentoRN->consultarRN0005($objDocumentoDTO);
            
            if (!$objDocumento) {
                $this->retornarErro(404, 'Documento não encontrado', 'DOCUMENT_NOT_FOUND');
                return;
            }
            
            // Verifica se o documento é público
            if ($objDocumento->getStrStaNivelAcessoLocalProtocolo() !== ProtocoloRN::$NA_PUBLICO) {
                $this->retornarErro(403, 'Acesso negado ao documento', 'ACCESS_DENIED');
                return;
            }
            
            $dadosDocumento = $this->formatarDocumentoDetalhado($objDocumento);
            
            $this->retornarSucesso($dadosDocumento, 'Documento obtido com sucesso');
            
        } catch (Exception $e) {
            $this->retornarErro(500, 'Erro ao obter documento', 'GET_ERROR', $e->getMessage());
        }
    }
    
    /**
     * Obtém o conteúdo de um documento
     * GET /api/v1/documents/{id}/content
     */
    public function obterConteudo($parametros = array())
    {
        try {
            if (!isset($parametros['id'])) {
                $this->retornarErro(400, 'ID do documento é obrigatório', 'MISSING_DOCUMENT_ID');
                return;
            }
            
            $idDocumento = $parametros['id'];
            $formato = isset($_GET['format']) ? $_GET['format'] : 'html';
            
            // Valida formato
            $formatosPermitidos = array('html', 'text', 'pdf');
            if (!in_array($formato, $formatosPermitidos)) {
                $this->retornarErro(400, 'Formato não suportado. Formatos válidos: ' . implode(', ', $formatosPermitidos), 'INVALID_FORMAT');
                return;
            }
            
            // Verifica se o documento existe e é público
            $objDocumentoDTO = new DocumentoDTO();
            $objDocumentoDTO->setDblIdDocumento($idDocumento);
            $objDocumentoDTO->retStrStaNivelAcessoLocalProtocolo();
            $objDocumentoDTO->retStrStaDocumento();
            $objDocumentoDTO->retNumIdSerie();
            
            $objDocumentoRN = new DocumentoRN();
            $objDocumento = $objDocumentoRN->consultarRN0005($objDocumentoDTO);
            
            if (!$objDocumento) {
                $this->retornarErro(404, 'Documento não encontrado', 'DOCUMENT_NOT_FOUND');
                return;
            }
            
            if ($objDocumento->getStrStaNivelAcessoLocalProtocolo() !== ProtocoloRN::$NA_PUBLICO) {
                $this->retornarErro(403, 'Acesso negado ao documento', 'ACCESS_DENIED');
                return;
            }
            
            // Obtém o conteúdo do documento
            $conteudo = $this->obterConteudoDocumento($idDocumento, $formato);
            
            if ($conteudo === false) {
                $this->retornarErro(404, 'Conteúdo do documento não encontrado', 'CONTENT_NOT_FOUND');
                return;
            }
            
            $dados = array(
                'document_id' => $idDocumento,
                'format' => $formato,
                'content' => $conteudo,
                'size' => strlen($conteudo)
            );
            
            $this->retornarSucesso($dados, 'Conteúdo obtido com sucesso');
            
        } catch (Exception $e) {
            $this->retornarErro(500, 'Erro ao obter conteúdo do documento', 'GET_CONTENT_ERROR', $e->getMessage());
        }
    }
    
    /**
     * Pesquisa documentos
     * GET /api/v1/documents/search
     */
    public function pesquisar($parametros = array())
    {
        try {
            $termo = isset($_GET['q']) ? trim($_GET['q']) : '';
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
            $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
            $tipoSerie = isset($_GET['series_type']) ? trim($_GET['series_type']) : '';
            $dataInicio = isset($_GET['date_from']) ? trim($_GET['date_from']) : '';
            $dataFim = isset($_GET['date_to']) ? trim($_GET['date_to']) : '';
            
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
            
            // Constrói critérios de pesquisa
            $objPesquisaProtocoloDTO = new PesquisaProtocoloDTO();
            $objPesquisaProtocoloDTO->setStrStaTipo(ProtocoloRN::$TPP_DOCUMENTOS);
            $objPesquisaProtocoloDTO->setStrStaAcesso(ProtocoloRN::$NA_PUBLICO);
            $objPesquisaProtocoloDTO->setStrPalavrasChave($termo);
            $objPesquisaProtocoloDTO->setNumMaxRegistrosRetorno($limit + $offset);
            
            // Filtros adicionais
            if (!empty($dataInicio)) {
                $objPesquisaProtocoloDTO->setDthGeracao($dataInicio, InfraDTO::$OPER_MAIOR_IGUAL);
            }
            
            if (!empty($dataFim)) {
                $objPesquisaProtocoloDTO->setDthGeracao($dataFim, InfraDTO::$OPER_MENOR_IGUAL);
            }
            
            $objProtocoloRN = new ProtocoloRN();
            $arrDocumentos = $objProtocoloRN->pesquisarRN0967($objPesquisaProtocoloDTO);
            
            $resultados = array();
            $count = 0;
            
            foreach ($arrDocumentos as $documento) {
                if ($count < $offset) {
                    $count++;
                    continue;
                }
                
                if (count($resultados) >= $limit) {
                    break;
                }
                
                // Filtro adicional por tipo de série se especificado
                if (!empty($tipoSerie) && stripos($documento->getStrNomeSerieDocumento(), $tipoSerie) === false) {
                    $count++;
                    continue;
                }
                
                $resultados[] = $this->formatarDocumentoPesquisa($documento);
                $count++;
            }
            
            $dados = array(
                'query' => $termo,
                'filters' => array(
                    'series_type' => $tipoSerie,
                    'date_from' => $dataInicio,
                    'date_to' => $dataFim
                ),
                'results' => $resultados,
                'total' => count($arrDocumentos),
                'limit' => $limit,
                'offset' => $offset,
                'returned' => count($resultados)
            );
            
            $this->retornarSucesso($dados, 'Pesquisa realizada com sucesso');
            
        } catch (Exception $e) {
            $this->retornarErro(500, 'Erro ao pesquisar documentos', 'SEARCH_ERROR', $e->getMessage());
        }
    }
    
    /**
     * Obtém o conteúdo real do documento
     */
    private function obterConteudoDocumento($idDocumento, $formato)
    {
        try {
            // Esta é uma implementação simplificada
            // Em um sistema real, você precisaria acessar o conteúdo do documento
            // através dos métodos apropriados do SEI
            
            switch ($formato) {
                case 'html':
                    return $this->obterConteudoHtml($idDocumento);
                    
                case 'text':
                    return $this->obterConteudoTexto($idDocumento);
                    
                case 'pdf':
                    return $this->obterConteudoPdf($idDocumento);
                    
                default:
                    return false;
            }
            
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Obtém conteúdo HTML do documento
     */
    private function obterConteudoHtml($idDocumento)
    {
        // Implementação específica para obter HTML
        // Retorna conteúdo mockado por enquanto
        return '<p>Conteúdo HTML do documento ' . $idDocumento . '</p>';
    }
    
    /**
     * Obtém conteúdo texto do documento
     */
    private function obterConteudoTexto($idDocumento)
    {
        // Implementação específica para obter texto
        // Retorna conteúdo mockado por enquanto
        return 'Conteúdo em texto do documento ' . $idDocumento;
    }
    
    /**
     * Obtém conteúdo PDF do documento (base64)
     */
    private function obterConteudoPdf($idDocumento)
    {
        // Implementação específica para obter PDF
        // Retorna conteúdo mockado por enquanto
        return base64_encode('PDF content for document ' . $idDocumento);
    }
    
    /**
     * Formata dados detalhados do documento
     */
    private function formatarDocumentoDetalhado($documento)
    {
        return array(
            'id' => $documento->getDblIdDocumento(),
            'protocol' => $documento->getStrProtocoloDocumentoFormatado(),
            'series' => array(
                'id' => $documento->getNumIdSerie(),
                'name' => $documento->getStrNomeSerie()
            ),
            'number' => $documento->getStrNumero(),
            'generation_date' => $documento->getDthGeracao(),
            'status' => $documento->getStrStaDocumento(),
            'access_level' => $documento->getStrStaNivelAcessoLocalProtocolo(),
            'global_access_level' => $documento->getStrStaNivelAcessoGlobalProtocolo(),
            'process_id' => $documento->getDblIdProcedimento(),
            'generating_unit' => array(
                'id' => $documento->getNumIdUnidadeGeradoraProtocolo(),
                'acronym' => $documento->getStrSiglaUnidadeGeradoraProtocolo(),
                'description' => $documento->getStrDescricaoUnidadeGeradoraProtocolo()
            ),
            'organ' => array(
                'id' => $documento->getNumIdOrgaoUnidadeGeradoraProtocolo(),
                'acronym' => $documento->getStrSiglaOrgaoUnidadeGeradoraProtocolo(),
                'description' => $documento->getStrDescricaoOrgaoUnidadeGeradoraProtocolo()
            ),
            'observation' => $documento->getStrObservacao()
        );
    }
    
    /**
     * Formata documento para resultado de pesquisa
     */
    private function formatarDocumentoPesquisa($documento)
    {
        return array(
            'id' => $documento->getDblIdProtocolo(),
            'protocol' => $documento->getStrProtocoloFormatado(),
            'series' => $documento->getStrNomeSerieDocumento(),
            'generation_date' => $documento->getDthGeracao(),
            'generating_unit' => array(
                'acronym' => $documento->getStrSiglaUnidadeGeradora(),
                'description' => $documento->getStrDescricaoUnidadeGeradora()
            ),
            'organ' => array(
                'acronym' => $documento->getStrSiglaOrgaoUnidadeGeradora(),
                'description' => $documento->getStrDescricaoOrgaoUnidadeGeradora()
            )
        );
    }
}