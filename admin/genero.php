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
                                <input class="form-control inputbig" type="text" v-model="form.name" placeholder="Nome" name="name" id="name" maxlength="100">
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
                                        <span v-if="item.active == 0">
                                            Sincronizar
                                        </span>
                                        <span v-if="item.active == 1">
                                            Sincronizado
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
                <button v-on:click="populateBases()" type="button" class="btn btn-sm">
                    Sincronizar
                </button>
                <br />
                <br />
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th scope="col">Nome</th>
                            <th scope="col">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="grids.genre.items.length == 0"><td colspan="2">Nenhum registro encontrado.</td></tr>
                        <tr v-for="item in grids.genre.items">
                            <th scope="col">
                                {{item.name}}
                            </th>
                            <td>
                                <button v-on:click="edit(item)" type="button" class="btn btn-sm">
                                    Editar
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

    Vue.component('modal', {
        template: '#modal-template'
    });

    new Vue({
        el: '#app',
        components: {
            Loading: VueLoading,
        },
        mounted() {
            //this.checkAllowed();
            this.populateGenre();
        },
        computed: {
        
        },
        data () {
            return {
                allowed: true,
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
                    id: 0,
                    name: '',
                },
                grids: {
                    genre: {
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
                        this.populateGenre();
                    }
                }, err => {
                    this.isLoading = false;
                    console.log(2,err)
                });
            },
            base_add(item) {
                if (item.active == 1) return;

                config.setapikey();
                let url = config.api + `/v1/admin/genre/base/save`;
                this.loading = true;
                let obj = {
                    id_base: item.id_base
                };

                Vue.http.post(url, obj, { emulateJSON: true }).then(res => {
                    this.loading = false;
                    if (res.body.success) {
                        alert(res.body.msg);
                        this.populateBases();
                        //this.showModalList = false;
                    }
                    else {
                        alert("Ocorreu uma falha ao tentar salvar.");
                    }
                }, err => {
                    this.loading = false;
                    console.log(2,err)
                });
            },
            save() {
                config.setapikey();
                let url = config.api + `/v1/admin/genre/save`;
                this.loading = true;
                let obj = {
                    id: this.form.id,
                    name: this.form.name,
                };

                Vue.http.post(url, obj, { emulateJSON: true }).then(res => {
                    this.loading = false;
                    if (res.body.success) {
                        alert("Salvo com sucesso.");
                        this.showModal = false;
                        this.populateGenre();
                    }
                    else {
                        alert("Ocorreu uma falha ao tentar salvar.");
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
                this.form.id = 0;
                this.form.name = "";
                this.showModal = true;
            },
            edit(item) {
                this.form.id = item.id;
                this.form.name = item.name;
                this.showModal = true;
            },
            regenerate(item) {

            },
            populateGenre() {
                config.setapikey();
                let url = config.api + `/v1/admin/genre/list`;
                this.loading = true;
                Vue.http.get(url).then(res => {
                    this.loading = false;
                    this.grids.genre.loaded = true;
                    this.grids.genre.items = res.body;
                }, err => {
                    this.loading = false;
                    console.log(2,err)
                });
            },
            populateBases() {
                config.setapikey();
                let url = config.api + `/v1/admin/genre/base/list`;
                this.loading = true;
                Vue.http.get(url).then(res => {
                    this.loading = false;
                    this.grids.bases.loaded = true;
                    this.grids.bases.items = res.body;
                    this.showModalList = true;
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