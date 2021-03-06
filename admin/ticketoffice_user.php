<?php
require_once($_SERVER['DOCUMENT_ROOT']."/settings/unique_ui.php");
require_once($_SERVER['DOCUMENT_ROOT'].'/admin/acessoLogado.php');
?>
<?php require_once($_SERVER['DOCUMENT_ROOT'].'/admin/header.php'); ?>

<?php //html code here ?>


<script type="text/x-template" id="modal-template">
  <transition name="modal">
    <div class="modal-mask">
      <div class="modal-wrapper">
        <div class="modal-container">

          <div class="modal-header">
            <slot name="header">
              
            </slot>
          </div>

          <div class="modal-body">
            <slot name="body">
              
            </slot>
          </div>

          <div class="modal-footer">
            <slot name="footer">
            <button class="modal-default-button" @click="$emit('close')">
                OK
              </button>
              <button class="modal-default-button" @click="$emit('close')">
                Fechar
              </button>
            </slot>
          </div>
        </div>
      </div>
    </div>
  </transition>
</script>

<div id="content">
    <div id="app" v-if="allowed">
        <template>
            <loading :active.sync="loading" :can-cancel="false" :is-full-page="true"></loading>
            <div v-if="errors.haveError && errors.isConnection" class="alert alert-danger alert-dismissible fade show" role="alert">
                <strong>Opss... </strong> Ocorreu um erro. ({{errors.message}})
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true" v-on:click="errors.haveError=false;errors.isConnection=false;">&times;</span>
                </button>
            </div>

            <modal v-if="showModal" @close="showModal = false">
                <!--
                you can use custom content here to overwrite
                default content
                -->
                <div slot="body">
                <div class="row">
                        <div class="form-group">
                            <div class="input-group input-group-sm">
                                <div class="input-group-prepend">
                                    <span class="input-group-text labelinput" id="inputGroup-sizing">Nome</span>
                                </div>
                                <input class="form-control inputbig" type="text" v-model="form.name" placeholder="Nome" name="name" id="name" maxlength="1000">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="form-group">
                            <div class="input-group input-group-sm">
                                <div class="input-group-prepend">
                                    <span class="input-group-text labelinput" id="inputGroup-sizing">Login</span>
                                </div>
                                <input class="form-control inputbig" type="text" v-model="form.login" placeholder="Login" name="login" id="login" maxlength="1000">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="form-group">
                            <div class="input-group input-group-sm">
                                <div class="input-group-prepend">
                                    <span class="input-group-text labelinput" id="inputGroup-sizing">E-Mail</span>
                                </div>
                                <input class="form-control inputbig" type="text" v-model="form.email" placeholder="e-mail" name="email" id="email" maxlength="1000">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="form-group">
                            <div class="input-group input-group-sm">
                                <span class="clickable" v-on:click="active"> {{form.active | active}} </span>
                            </div>
                        </div>
                    </div>
                </div>
                <div slot="footer">
                <button v-on:click="save"  type="button" class="btn btn-sm">
                            Salvar
                    </button>
                    <button v-on:click="showModal = false"  type="button" class="btn btn-sm">
                            Cancelar
                    </button>
                </div>
            </modal>
            <modal v-if="showModalList" @close="showModalList = false">
                <!--
                you can use custom content here to overwrite
                default content
                -->
                <div slot="header">
                    Bases
                </div>
                <div slot="body">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th scope="col">Base</th>
                                <th scope="col">Nome</th>
                                <th scope="col">Ação</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="item in grids.bases.items">
                                <td>{{item.ds_nome_base_sql}}</td>
                                <td>{{item.ds_nome_teatro}}</td>
                                <td>
                                    <button v-on:click="base_add(item)" type="button" class="btn btn-sm">
                                        <span v-if="item.active==1">
                                            Remover
                                        </span>
                                        <span v-if="item.active==0">
                                            Adicionar
                                        </span>
                                    </button>                                
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div slot="footer">
                    <button v-on:click="showModalList = false"  type="button" class="btn btn-sm">
                            Cancelar
                    </button>
                </div>
            </modal>
            <br />
            <div v-if="!showModal">
                <button v-on:click="add()" type="button" class="btn btn-sm">
                    Novo
                </button>
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th scope="col">Nome</th>
                            <th scope="col">Login</th>
                            <th scope="col">E-Mail</th>
                            <th scope="col">Ativo?</th>
                            <th scope="col">Criado em</th>
                            <th scope="col">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="grids.users.items.length == 0"><td colspan="6">Nenhum registro encontrado.</td></tr>
                        <tr v-for="item in grids.users.items">
                            <th scope="col">
                                {{item.name}}
                            </th>
                            <td>{{item.login}}</td>
                            <td>{{item.email}}</td>
                            <td>{{item.active | truefalse }}</td>
                            <td>{{item.created}}</td>
                            <td>
                                <button v-on:click="bases(item)" type="button" class="btn btn-sm">
                                    Bases
                                </button>
                                <button v-on:click="edit(item)" type="button" class="btn btn-sm">
                                    Editar
                                </button>
                                <button v-on:click="resetpass(item.id)" type="button" class="btn btn-sm">
                                    Resetar senha
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </template>
    </div>
</div>

<?php //html code end here?>
<?php //javascript code end here?>

<script>
    Vue.use(VueLoading);
    Vue.use(VueTheMask);

    Vue.component('modal', {
        template: '#modal-template'
    });

    Vue.filter('truefalse', function (value) {
        return value == 1 || value == "1" || value == true ? "Sim" : "Não";
    });
    Vue.filter('active', function (value) {
        return value == 1 || value == "1" || value == true ? "Ativo" : "Inativo";
    });

    new Vue({
        el: '#app',
        directives: {VueTheMask},
        components: {
            Loading: VueLoading,
        },
        mounted() {
            this.checkAllowed();
        },
        computed: {
        
        },
        data () {
            return {
                allowed: true,//false,
                loading: false,
                executing: false,
                showModal: false,
                showModalList: false,
                errors: {
                    haveError: false,
                    isConnection: false,
                    isExecution: false,
                    message: '',
                },
                executing: {
                },
                form: {
                    id: null,
                    name: null,
                    login: null,
                    email: null,
                    active: 0,
                },
                grids: {
                    users: {
                        loaded: false,
                        items: [],
                    },
                    bases: {
                        loaded: false,
                        items: [],
                    }
                }
            }
        },
        methods: {
            checkAllowed() {
                let url = config.api +'/v1/admin/authorization/check?id_programa=668&id_user=<?php echo $_SESSION["admin"]?>';
                this.isLoading = true;
                Vue.http.get(url).then(res => {
                    this.allowed = res.body.allowed;
                    this.isLoading = false;
                    if (this.allowed) {
                        this.populateUser();
                    }
                }, err => {
                    this.isLoading = false;
                    console.log(2,err)
                });
            },
            base_add(item) {
                config.setapikey();
                let url = config.api + `/v1/admin/tolegacy/user/base/save`;
                this.loading = true;
                let obj = {
                    id: this.form.id,
                    id_base: item.id_base
                };

                Vue.http.post(url, obj, { emulateJSON: true }).then(res => {
                    this.loading = false;
                    if (res.body.success) {
                        alert("Executado com sucesso.");
                        this.populateBases();
                        //this.showModalList = false;
                    }
                    else {
                        let msg = res.body.msg;
                        msg = msg == '' ? "Ocorreu uma falha ao tentar salvar." : msg;
                        alert(msg);
                    }
                }, err => {
                    this.loading = false;
                    console.log(2,err)
                });
            },
            save() {
                config.setapikey();
                let url = config.api + `/v1/admin/tolegacy/user/save`;
                this.loading = true;
                let obj = {
                    id: this.form.id == null ? '' : this.form.id,
                    name: this.form.name,
                    login: this.form.login,
                    email: this.form.email,
                    active: this.form.active,
                };

                Vue.http.post(url, obj, { emulateJSON: true }).then(res => {
                    this.loading = false;
                    if (res.body.success) {
                        alert("Salvo com sucesso.");
                        this.showModal = false;
                        this.populateUser();
                    }
                    else {
                        let msg = res.body.msg;
                        msg = msg == '' ? "Ocorreu uma falha ao tentar salvar." : msg;
                        alert(msg);
                    }
                }, err => {
                    this.loading = false;
                    console.log(2,err)
                });
            },
            resetpass(id) {
                config.setapikey();
                let url = config.api + `/v1/admin/tolegacy/user/resetpass`;
                this.loading = true;
                let obj = {
                    id: id,
                };

                Vue.http.post(url, obj, { emulateJSON: true }).then(res => {
                    this.loading = false;
                    if (res.body.success) {
                        alert("Senha resetada com sucesso.");
                        this.showModal = false;
                        this.populateUser();
                    }
                    else {
                        let msg = res.body.msg;
                        msg = msg == '' ? "Ocorreu uma falha ao tentar salvar." : msg;
                        alert(msg);
                    }
                }, err => {
                    this.loading = false;
                    console.log(2,err)
                });
            },
            active() {
                this.form.active = this.form.active == 1 ? 0 : 1;
            },
            add() {
                this.form.name = "";
                this.form.login = "";
                this.form.active = 1;
                this.form.email = "";
                this.showModal = true;
            },
            bases(item) {
                this.form.id = item.id;
                this.showModalList = true;
                this.populateBases();
            },
            edit(item) {
                this.form.id = item.id;
                this.form.name = item.name;
                this.form.login = item.login;
                this.form.active = item.active;
                this.form.email = item.email;
                this.showModal = true;
            },
            populateUser() {
                config.setapikey();
                let url = config.api + `/v1/admin/tolegacy/user/list`;
                this.loading = true;
                Vue.http.get(url).then(res => {
                    this.loading = false;
                    this.grids.users.loaded = true;
                    this.grids.users.items = res.body;
                }, err => {
                    this.loading = false;
                    console.log(2,err)
                });
            },
            populateBases() {
                config.setapikey();
                let url = config.api + `/v1/admin/tolegacy/user/base/list?id=${this.form.id}`;
                this.loading = true;
                Vue.http.get(url).then(res => {
                    this.loading = false;
                    this.grids.bases.loaded = true;
                    this.grids.bases.items = res.body;
                    //console.log(this.grids.bases.items);
                }, err => {
                    this.loading = false;
                    console.log(2,err)
                });
            },
        }
    });
</script>

<style>
    #app .card {
        width: 100% !important;
    }
    #app .table {
        margin-left: 10px;
    }
    .grids {
        margin-left: 10px;
    }
    #content {
        height: auto;
        position: relative;
    }
    #app {
        height:  auto;
    }
    .clickable {
        cursor: pointer;
    }
    .canceled {
        text-decoration: line-through;
    }
    .quill-editor {
        width: 96%;
    }
    .preview-container[data-v-152c2f15] {
        margin-left:0px;
    }
    .inputbig {
        width: 444px !important
    }

    .modal-body {
        overflow-y: scroll;
    }

</style>
<link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.2.0/css/solid.css" integrity="sha384-wnAC7ln+XN0UKdcPvJvtqIH3jOjs9pnKnq9qX68ImXvOGz2JuFoEiCjT8jyZQX2z" crossorigin="anonymous">
<link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.2.0/css/fontawesome.css" integrity="sha384-HbmWTHay9psM8qyzEKPc8odH4DsOuzdejtnr+OFtDmOcIVnhgReQ4GZBH7uwcjf6" crossorigin="anonymous">


<?php require_once($_SERVER['DOCUMENT_ROOT'].'/admin/footer.php'); ?>