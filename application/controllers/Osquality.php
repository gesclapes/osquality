<?php if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}
class OsQuality extends MY_Controller
{

    

    public function __construct()
    {
        parent::__construct();
        $this->load->model('osquality_model');
    }

    public function index()
    {
        $this->data['ordens'] = $this->osquality_model->getOsAbertas();
        $this->data['ordens_andamento'] = $this->osquality_model->getOsAndamento();
        $this->data['menuPainel'] = 'Painel';
        $this->data['view'] = 'osquality/painel';
        return $this->layout();
    }

    public function minhaConta()
    {
        $this->data['usuario'] = $this->osquality_model->getById($this->session->userdata('id'));
        $this->data['view'] = 'osquality/minhaConta';
        return $this->layout();
    }

    public function alterarSenha()
    {
        $current_user = $this->osquality_model->getById($this->session->userdata('id'));

        if (!$current_user) {
            $this->session->set_flashdata('error', 'Ocorreu um erro ao pesquisar usuário!');
            redirect(site_url('osquality/minhaConta'));
        }

        $oldSenha = $this->input->post('oldSenha');
        $senha = $this->input->post('novaSenha');

        if (!password_verify($oldSenha, $current_user->senha)) {
            $this->session->set_flashdata('error', 'A senha atual não corresponde com a senha informada.');
            redirect(site_url('osquality/minhaConta'));
        }

        $result = $this->osquality_model->alterarSenha($senha);

        if ($result) {
            $this->session->set_flashdata('success', 'Senha alterada com sucesso!');
            redirect(site_url('osquality/minhaConta'));
        }

        $this->session->set_flashdata('error', 'Ocorreu um erro ao tentar alterar a senha!');
        redirect(site_url('osquality/minhaConta'));
    }

    public function pesquisar()
    {
        $termo = $this->input->get('termo');

        $data['results'] = $this->osquality_model->pesquisar($termo);
        $this->data['produtos'] = $data['results']['produtos'];
        $this->data['servicos'] = $data['results']['servicos'];
        $this->data['os'] = $data['results']['os'];
        $this->data['clientes'] = $data['results']['clientes'];
        $this->data['view'] = 'osquality/pesquisa';
        return $this->layout();
    }

    public function backup()
    {
        if (!$this->permission->checkPermission($this->session->userdata('permissao'), 'cBackup')) {
            $this->session->set_flashdata('error', 'Você não tem permissão para efetuar backup.');
            redirect(base_url());
        }

        $this->load->dbutil();
        $prefs = [
            'format' => 'zip',
            'foreign_key_checks' => false,
            'filename' => 'backup' . date('d-m-Y') . '.sql',
        ];

        $backup = $this->dbutil->backup($prefs);

        $this->load->helper('file');
        write_file(base_url() . 'backup/backup.zip', $backup);

        log_info('Efetuou backup do banco de dados.');

        $this->load->helper('download');
        force_download('backup' . date('d-m-Y H:m:s') . '.zip', $backup);
    }

    public function emitente()
    {
        if (!$this->permission->checkPermission($this->session->userdata('permissao'), 'cEmitente')) {
            $this->session->set_flashdata('error', 'Você não tem permissão para configurar emitente.');
            redirect(base_url());
        }

        $this->data['menuConfiguracoes'] = 'Configuracoes';
        $this->data['dados'] = $this->osquality_model->getEmitente();
        $this->data['view'] = 'osquality/emitente';
        return $this->layout();
    }

    public function do_upload()
    {
        if (!$this->permission->checkPermission($this->session->userdata('permissao'), 'cEmitente')) {
            $this->session->set_flashdata('error', 'Você não tem permissão para configurar emitente.');
            redirect(base_url());
        }

        $this->load->library('upload');

        $image_upload_folder = FCPATH . 'assets/uploads';

        if (!file_exists($image_upload_folder)) {
            mkdir($image_upload_folder, DIR_WRITE_MODE, true);
        }

        $this->upload_config = [
            'upload_path' => $image_upload_folder,
            'allowed_types' => 'png|jpg|jpeg|bmp',
            'max_size' => 2048,
            'remove_space' => true,
            'encrypt_name' => true,
        ];

        $this->upload->initialize($this->upload_config);

        if (!$this->upload->do_upload()) {
            $upload_error = $this->upload->display_errors();
            print_r($upload_error);
            exit();
        } else {
            $file_info = [$this->upload->data()];
            return $file_info[0]['file_name'];
        }
    }

    public function do_upload_user()
    {
        if (!$this->permission->checkPermission($this->session->userdata('permissao'), 'cEmitente')) {
            $this->session->set_flashdata('error', 'Você não tem permissão para configurar emitente.');
            redirect(base_url());
        }

        $this->load->library('upload');

        $image_upload_folder = FCPATH . 'assets/userImage/';

        if (!file_exists($image_upload_folder)) {
            mkdir($image_upload_folder, DIR_WRITE_MODE, true);
        }

        $this->upload_config = [
            'upload_path' => $image_upload_folder,
            'allowed_types' => 'png|jpg|jpeg|bmp',
            'max_size' => 2048,
            'remove_space' => true,
            'encrypt_name' => true,
        ];

        $this->upload->initialize($this->upload_config);

        if (!$this->upload->do_upload()) {
            $upload_error = $this->upload->display_errors();
            print_r($upload_error);
            exit();
        } else {
            $file_info = [$this->upload->data()];
            return $file_info[0]['file_name'];
        }
    }

    public function cadastrarEmitente()
    {
        if (!$this->permission->checkPermission($this->session->userdata('permissao'), 'cEmitente')) {
            $this->session->set_flashdata('error', 'Você não tem permissão para configurar emitente.');
            redirect(base_url());
        }

        $this->load->library('form_validation');
        $this->form_validation->set_rules('nome', 'Razão Social', 'required|trim');
        $this->form_validation->set_rules('cnpj', 'CNPJ', 'required|trim');
        $this->form_validation->set_rules('ie', 'IE', 'required|trim');
        $this->form_validation->set_rules('cep', 'CEP', 'required|trim');
        $this->form_validation->set_rules('logradouro', 'Logradouro', 'required|trim');
        $this->form_validation->set_rules('numero', 'Número', 'required|trim');
        $this->form_validation->set_rules('bairro', 'Bairro', 'required|trim');
        $this->form_validation->set_rules('cidade', 'Cidade', 'required|trim');
        $this->form_validation->set_rules('uf', 'UF', 'required|trim');
        $this->form_validation->set_rules('telefone', 'Telefone', 'required|trim');
        $this->form_validation->set_rules('email', 'E-mail', 'required|trim');

        if ($this->form_validation->run() == false) {
            $this->session->set_flashdata('error', 'Campos obrigatórios não foram preenchidos.');
            redirect(site_url('osquality/emitente'));
        } else {
            $nome = $this->input->post('nome');
            $cnpj = $this->input->post('cnpj');
            $ie = $this->input->post('ie');
            $cep = $this->input->post('cep');
            $logradouro = $this->input->post('logradouro');
            $numero = $this->input->post('numero');
            $bairro = $this->input->post('bairro');
            $cidade = $this->input->post('cidade');
            $uf = $this->input->post('uf');
            $telefone = $this->input->post('telefone');
            $email = $this->input->post('email');
            $image = $this->do_upload();
            $logo = base_url() . 'assets/uploads/' . $image;

            $retorno = $this->osquality_model->addEmitente($nome, $cnpj, $ie, $cep, $logradouro, $numero, $bairro, $cidade, $uf, $telefone, $email, $logo);
            if ($retorno) {
                $this->session->set_flashdata('success', 'As informações foram inseridas com sucesso.');
                log_info('Adicionou informações de emitente.');
            } else {
                $this->session->set_flashdata('error', 'Ocorreu um erro ao tentar inserir as informações.');
            }
            redirect(site_url('osquality/emitente'));
        }
    }

    public function editarEmitente()
    {
        if (!$this->permission->checkPermission($this->session->userdata('permissao'), 'cEmitente')) {
            $this->session->set_flashdata('error', 'Você não tem permissão para configurar emitente.');
            redirect(base_url());
        }

        $this->load->library('form_validation');
        $this->form_validation->set_rules('nome', 'Razão Social', 'required|trim');
        $this->form_validation->set_rules('cnpj', 'CNPJ', 'required|trim');
        $this->form_validation->set_rules('ie', 'IE', 'required|trim');
        $this->form_validation->set_rules('cep', 'CEP', 'required|trim');
        $this->form_validation->set_rules('logradouro', 'Logradouro', 'required|trim');
        $this->form_validation->set_rules('numero', 'Número', 'required|trim');
        $this->form_validation->set_rules('bairro', 'Bairro', 'required|trim');
        $this->form_validation->set_rules('cidade', 'Cidade', 'required|trim');
        $this->form_validation->set_rules('uf', 'UF', 'required|trim');
        $this->form_validation->set_rules('telefone', 'Telefone', 'required|trim');
        $this->form_validation->set_rules('email', 'E-mail', 'required|trim');

        if ($this->form_validation->run() == false) {
            $this->session->set_flashdata('error', 'Campos obrigatórios não foram preenchidos.');
            redirect(site_url('osquality/emitente'));
        } else {
            $nome = $this->input->post('nome');
            $cnpj = $this->input->post('cnpj');
            $ie = $this->input->post('ie');
            $cep = $this->input->post('cep');
            $logradouro = $this->input->post('logradouro');
            $numero = $this->input->post('numero');
            $bairro = $this->input->post('bairro');
            $cidade = $this->input->post('cidade');
            $uf = $this->input->post('uf');
            $telefone = $this->input->post('telefone');
            $email = $this->input->post('email');
            $id = $this->input->post('id');

            $retorno = $this->osquality_model->editEmitente($id, $nome, $cnpj, $ie, $cep, $logradouro, $numero, $bairro, $cidade, $uf, $telefone, $email);
            if ($retorno) {
                $this->session->set_flashdata('success', 'As informações foram alteradas com sucesso.');
                log_info('Alterou informações de emitente.');
            } else {
                $this->session->set_flashdata('error', 'Ocorreu um erro ao tentar alterar as informações.');
            }
            redirect(site_url('osquality/emitente'));
        }
    }

    public function editarLogo()
    {
        if (!$this->permission->checkPermission($this->session->userdata('permissao'), 'cEmitente')) {
            $this->session->set_flashdata('error', 'Você não tem permissão para configurar emitente.');
            redirect(base_url());
        }

        $id = $this->input->post('id');
        if ($id == null || !is_numeric($id)) {
            $this->session->set_flashdata('error', 'Ocorreu um erro ao tentar alterar a logomarca.');
            redirect(site_url('osquality/emitente'));
        }
        $this->load->helper('file');
        delete_files(FCPATH . 'assets/uploads/');

        $image = $this->do_upload();
        $logo = base_url() . 'assets/uploads/' . $image;

        $retorno = $this->osquality_model->editLogo($id, $logo);
        if ($retorno) {
            $this->session->set_flashdata('success', 'As informações foram alteradas com sucesso.');
            log_info('Alterou a logomarca do emitente.');
        } else {
            $this->session->set_flashdata('error', 'Ocorreu um erro ao tentar alterar as informações.');
        }
        redirect(site_url('osquality/emitente'));
    }

    public function uploadUserImage()
    {
        if (!$this->permission->checkPermission($this->session->userdata('permissao'), 'cUsuario')) {
            $this->session->set_flashdata('error', 'Você não tem permissão para mudar a foto.');
            redirect(base_url());
        }

        $id = $this->session->userdata('id');
        if ($id == null || !is_numeric($id)) {
            $this->session->set_flashdata('error', 'Ocorreu um erro ao tentar alterar sua foto.');
            redirect(site_url('osquality/minhaConta'));
        }

        $usuario = $this->osquality_model->getById($id);
        
        if (is_file(FCPATH . 'assets/userImage/' . $usuario->url_image_user)) {
            unlink(FCPATH . 'assets/userImage/' . $usuario->url_image_user);
        }

        $image = $this->do_upload_user();
        $imageUserPath = $image;
        $retorno = $this->osquality_model->editImageUser($id, $imageUserPath);
        
        if ($retorno) {
            $this->session->set_userdata('url_image_user', $imageUserPath);
            $this->session->set_flashdata('success', 'Foto alterada com sucesso.');
            log_info('Alterou a Imagem do Usuario.');
        } else {
            $this->session->set_flashdata('error', 'Ocorreu um erro ao tentar alterar sua foto.');
        }
        redirect(site_url('osquality/minhaConta'));
    }

    public function emails()
    {
        if (!$this->permission->checkPermission($this->session->userdata('permissao'), 'cEmail')) {
            $this->session->set_flashdata('error', 'Você não tem permissão para visualizar fila de e-mails');
            redirect(base_url());
        }

        $this->data['menuConfiguracoes'] = 'Email';

        $this->load->library('pagination');
        $this->load->model('email_model');

        $this->data['configuration']['base_url'] = site_url('osquality/emails/');
        $this->data['configuration']['total_rows'] = $this->email_model->count('email_queue');

        $this->pagination->initialize($this->data['configuration']);

        $this->data['results'] = $this->email_model->get('email_queue', '*', '', $this->data['configuration']['per_page'], $this->uri->segment(3));

        $this->data['view'] = 'emails/emails';
        return $this->layout();
    }

    public function excluirEmail()
    {
        if (!$this->permission->checkPermission($this->session->userdata('permissao'), 'cEmail')) {
            $this->session->set_flashdata('error', 'Você não tem permissão para excluir e-mail da fila.');
            redirect(base_url());
        }

        $id = $this->input->post('id');
        if ($id == null) {
            $this->session->set_flashdata('error', 'Erro ao tentar excluir e-mail da fila.');
            redirect(site_url('osquality/emails/'));
        }

        $this->load->model('email_model');
        $this->email_model->delete('email_queue', 'id', $id);

        log_info('Removeu um e-mail da fila de envio. ID: ' . $id);

        $this->session->set_flashdata('success', 'E-mail removido da fila de envio!');
        redirect(site_url('osquality/emails/'));
    }

    public function configurar()
    {
        if (!$this->permission->checkPermission($this->session->userdata('permissao'), 'cSistema')) {
            $this->session->set_flashdata('error', 'Você não tem permissão para configurar o sistema');
            redirect(base_url());
        }
        $this->data['menuConfiguracoes'] = 'Sistema';

        $this->load->library('form_validation');
        $this->load->model('osquality_model');

        $this->data['custom_error'] = '';

        $this->form_validation->set_rules('app_name', 'Nome do Sistema', 'required|trim');
        $this->form_validation->set_rules('per_page', 'Registros por página', 'required|numeric|trim');
        $this->form_validation->set_rules('app_theme', 'Tema do Sistema', 'required|trim');
        $this->form_validation->set_rules('os_notification', 'Notificação de OS', 'required|trim');
        $this->form_validation->set_rules('control_estoque', 'Controle de Estoque', 'required|trim');
        $this->form_validation->set_rules('notifica_whats', 'Notificação Whatsapp', 'required|trim');
        $this->form_validation->set_rules('control_baixa', 'Controle de Baixa', 'required|trim');
        $this->form_validation->set_rules('control_editos', 'Controle de Edição de OS', 'required|trim');
        $this->form_validation->set_rules('control_edit_vendas', 'Controle de Edição de Vendas', 'required|trim');
        $this->form_validation->set_rules('control_datatable', 'Controle de Visualização em DataTables', 'required|trim');
        $this->form_validation->set_rules('os_status_list[]', 'Controle de visualização de OS', 'required|trim', ['required' => 'Selecione ao menos uma das opções!']);
        $this->form_validation->set_rules('pix_key', 'Chave Pix', 'trim|valid_pix_key', [
            'valid_pix_key' => 'Chave Pix inválida!',
        ]);

        if ($this->form_validation->run() == false) {
            $this->data['custom_error'] = (validation_errors() ? '<div class="alert">' . validation_errors() . '</div>' : false);
        } else {
            $data = [
                'app_name' => $this->input->post('app_name'),
                'per_page' => $this->input->post('per_page'),
                'app_theme' => $this->input->post('app_theme'),
                'os_notification' => $this->input->post('os_notification'),
                'control_estoque' => $this->input->post('control_estoque'),
                'notifica_whats' => $this->input->post('notifica_whats'),
                'control_baixa' => $this->input->post('control_baixa'),
                'control_editos' => $this->input->post('control_editos'),
                'control_edit_vendas' => $this->input->post('control_edit_vendas'),
                'control_datatable' => $this->input->post('control_datatable'),
                'pix_key' => $this->input->post('pix_key'),
                'os_status_list' => json_encode($this->input->post('os_status_list')),
            ];
            if ($this->osquality_model->saveConfiguracao($data) == true) {
                $this->session->set_flashdata('success', 'Configurações do sistema atualizadas com sucesso!');
                redirect(site_url('osquality/configurar'));
            } else {
                $this->data['custom_error'] = '<div class="alert">Ocorreu um errro.</div>';
            }
        }

        $this->data['view'] = 'osquality/configurar';

        return $this->layout();
    }

    public function atualizarBanco()
    {
        if (!$this->permission->checkPermission($this->session->userdata('permissao'), 'cSistema')) {
            $this->session->set_flashdata('error', 'Você não tem permissão para configurar o sistema');
            redirect(base_url());
        }

        $this->load->library('migration');

        if ($this->migration->latest() === false) {
            $this->session->set_flashdata('error', $this->migration->error_string());
        } else {
            $this->session->set_flashdata('success', 'Banco de dados atualizado com sucesso!');
        }

        return redirect(site_url('osquality/configurar'));
    }

    public function atualizarosquality()
    {
        if (!$this->permission->checkPermission($this->session->userdata('permissao'), 'cSistema')) {
            $this->session->set_flashdata('error', 'Você não tem permissão para configurar o sistema');
            redirect(base_url());
        }

        $this->load->library('github_updater');

        if (!$this->github_updater->has_update()) {
            $this->session->set_flashdata('success', 'Seu osquality já está atualizado!');

            return redirect(site_url('osquality/configurar'));
        }

        $success = $this->github_updater->update();

        if ($success) {
            $this->session->set_flashdata('success', 'osquality atualizado com sucesso!');
        } else {
            $this->session->set_flashdata('error', 'Erro ao atualizar osquality!');
        }

        return redirect(site_url('osquality/configurar'));
    }

    public function calendario()
    {
        if (!$this->permission->checkPermission($this->session->userdata('permissao'), 'vOs')) {
            $this->session->set_flashdata('error', 'Você não tem permissão para visualizar O.S.');
            redirect(base_url());
        }
        $this->load->model('os_model');
        $status = $this->input->get('status') ?: null;
        $start = $this->input->get('start') ?: null;
        $end = $this->input->get('end') ?: null;

        $allOs = $this->osquality_model->calendario(
            $start,
            $end,
            $status
        );
        $events = array_map(function ($os) {
            switch ($os->status) {
                case 'Aberto':
                    $cor = '#00cd00';
                    break;
                case 'Negociação':
                    $cor = '#AEB404';
                    break;
                case 'Em Andamento':
                    $cor = '#436eee';
                    break;
                case 'Orçamento':
                    $cor = '#CDB380';
                    break;
                case 'Cancelado':
                    $cor = '#CD0000';
                    break;
                case 'Finalizado':
                    $cor = '#256';
                    break;
                case 'Faturado':
                    $cor = '#B266FF';
                    break;
                case 'Aguardando Peças':
                    $cor = '#FF7F00';
                    break;
                default:
                    $cor = '#E0E4CC';
                    break;
            }
            return [
                'title' => "OS: {$os->idOs}, Cliente: {$os->nomeCliente}",
                'start' => $os->dataFinal,
                'end' => $os->dataFinal,
                'color' => $cor,
                'extendedProps' => [
                    'id' => $os->idOs,
                    'cliente' => '<b>Cliente:</b> ' . $os->nomeCliente,
                    'dataInicial' => '<b>Data Inicial:</b> ' . $os->dataInicial,
                    'dataFinal' => '<b>Data Final:</b> ' . $os->dataFinal,
                    'garantia' => '<b>Garantia:</b> ' . $os->garantia,
                    'status' => '<b>Status da OS:</b> ' . $os->status,
                    'description' => '<b>Descrição/Produto:</b> ' . $os->descricaoProduto,
                    'defeito' => '<b>Defeito:</b> ' . $os->defeito,
                    'observacoes' => '<b>Observações:</b> ' . $os->observacoes,
                    'total' => '<b>Valor Total:</b> R$ ' . number_format($os->totalProdutos + $os->totalServicos, 2, ',', '.'),
                    'valorFaturado' => '<b>Valor Faturado:</b> R$ ' . number_format($os->valorTotal, 2, ',', '.'),
                    'editar' => $this->os_model->isEditable($os->idOs),
                ]
            ];
        }, $allOs);

        return $this->output
            ->set_content_type('application/json')
            ->set_status_header(200)
            ->set_output(json_encode($events));
    }
}
