<?php
require_once($_SERVER['DOCUMENT_ROOT']."/settings/unique_ui.php");
require_once($_SERVER['DOCUMENT_ROOT'].'/admin/acessoLogado.php');
?>
<?php require_once($_SERVER['DOCUMENT_ROOT'].'/admin/header.php'); ?>

<script src="https://cdn.quilljs.com/1.3.4/quill.js"></script>
<script src="https://cdn.jsdelivr.net/npm/vue-quill-editor@3.0.4/dist/vue-quill-editor.js"></script>
<link href="https://cdn.quilljs.com/1.3.4/quill.core.css" rel="stylesheet">
<link href="https://cdn.quilljs.com/1.3.4/quill.snow.css" rel="stylesheet">
<link href="https://cdn.quilljs.com/1.3.4/quill.bubble.css" rel="stylesheet">

<?php //html code here ?>

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
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            Cadastro de Informações de Eventos
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <div class="input-group input-group-sm mb-3 col-6">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text" id="inputGroup-sizing-sm">Bases</span>
                                    </div>
                                    <select v-model="form.id_base" v-on:change="populateEvents" class="form-control" style="width: 30%" name="id_base" id="id_base">
                                        <option selected disabled>Escolha</option>
                                        <option v-for="option in optionsBases" v-bind:value="option.value">
                                            {{ option.text }}
                                        </option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="input-group input-group-sm mb-3 col-6">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text" id="inputGroup-sizing-sm">Evento</span>
                                    </div>
                                    <select v-model="form.id_evento" class="form-control" v-on:change="populatePresentation" style="width: 30%" name="id_evento" id="id_evento">
                                        <option selected disabled>Escolha</option>
                                        <option v-for="option in optionsEvents" v-bind:value="option.value">
                                            {{ option.text }}
                                        </option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="input-group input-group-sm mb-3 col-6">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text" id="inputGroup-sizing-sm">Apresentacao</span>
                                    </div>
                                    <select v-model="form.id_apresentacao" class="form-control" v-on:change="getOffer" style="width: 30%" name="id_apresentacao" id="id_apresentacao">
                                        <option selected disabled>Escolha</option>
                                        <option v-for="option in optionsApresentacao" v-bind:value="option.value">
                                            {{ option.text }}
                                        </option>
                                    </select>
                                </div>
                            </div>
                        </div>                       
                    </div>
                </div>
            </div>
            <br />
            <br />
            <div class="card" v-if="form.loaded">
                <div class="card-header">
                    <div class="row justify-content-between">
                        <div class="col-4">Informações da oferta</div>
                        <div class="col-4">
                            <button v-on:click="save" type="button" class="btn btn-sm" style="float: right;">
                                    Salvar
                            </button>
                        </div>
                    </div>                    
                </div>
                <div style="padding-left: 25px; padding-top: 15px;">
                    <div class="row">
                        <div class="form-group" style="margin-left: 26px;">
                            <input id="onOffer" type="checkbox" name="onOffer" v-model="form.onOffer" autocomplete="off" true-value="1" class="custom-control-input" value="1">
                            <label for="onOffer" class="custom-control-label" style="padding-top: 6px;">
                                <span>Em Oferta</span>
                            </label>
                        </div>
                    </div>
                    <div class="row">
                        <div class="form-group">
                            <div class="input-group input-group-sm">
                            <div class="input-group-prepend">
                                    <span class="input-group-text labelinput" id="inputGroup-sizing">Valor Antigo</span>
                                </div>
                                <div class="input-group-prepend" style="width:32px">
                                    <span class="input-group-text labelinput" id="inputGroup-sizing2">R$</span>
                                </div>
                                <input class="form-control inputbig" type="text" v-mask="['####,##']" :disabled="form.onOffer != 1" v-model="form.onOfferOlderValue" placeholder="Valor antigo" name="onOfferOlderValue" id="onOfferOlderValue" maxlength="7">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="form-group">
                            <div class="input-group input-group-sm">
                                <div class="input-group-prepend">
                                    <span class="input-group-text labelinput" id="inputGroup-sizing">Porcentagem desconto</span>
                                </div>
                                <div class="input-group-prepend" style="width:32px">
                                    <span class="input-group-text labelinput" id="inputGroup-sizing2">%</span>
                                </div>
                                <input class="form-control inputbig" type="text" v-mask="['###']" :disabled="form.onOffer != 1" v-model="form.onOfferPercentage" placeholder="porcentagem desconto" name="onOfferPercentage" id="onOfferPercentage" maxlength="3">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <span id="inputGroup-sizing-sm" style="padding-bottom: 10px; padding-top:20px;">Texto extra de oferta</span>
                    </div>
                    <div class="row" style="height:150px; margin-bottom:50px;">
                        <quill-editor v-model="form.onOfferText" :disabled="form.onOffer != 1"
                            ref="editor"
                            :options="quillOptions"
                            @blur="onEditorBlur($event)"
                            @focus="onEditorFocus($event)"
                            @ready="onEditorReady($event)">
                        </quill-editor>

                    </div>
                </div>
            </div>
        </template>
    </div>
</div>

<?php //html code end here?>
<?php //javascript code end here?>

<script>
    Vue.use(VueLoading);
    Vue.use(VueTheMask);

    Vue.use(VueQuillEditor);

    //Vue.component('modal', {
    //    template: '#modal-template'
    //});


    new Vue({
        el: '#app',
        directives: {VueTheMask},
        components: {
            Loading: VueLoading,
            LocalQuillEditor: VueQuillEditor.quillEditor,
        },
        mounted() {
            this.checkAllowed();
            if (this.form.id_apresentacao!=null) {
                this.getOffer();
            }
        },
        computed: {
        
        },
        data () {
            return {
                allowed: false,
                loading: false,
                executing: false,
                quillOptions: {
                    modules: {
                    toolbar: [ [{ header: [1, 2, false] }],
                               ,['bold', 'italic', 'underline']
                               ,[{ 'color': [] }, { 'background': [] }]
                               ,[{ 'align': [] }]
                               ,['clean']
                             ]
                    },
                    placeholder: 'texto da oferta',
                    theme: 'snow'
                },
                errors: {
                    haveError: false,
                    isConnection: false,
                    isExecution: false,
                    message: '',
                },
                executing: {
                },
                form: {
                    hasImage: true,
                    loaded: false,
                    id_base: null,
                    id_evento: null,//22666,
                    id_apresentacao: null,//167433,//null,
                    onOffer: false,
                    onOfferOlderValue: '',
                    onOfferPercentage: '',
                    onOfferText: '',
                },
                optionsBases : [],
                optionsEvents : [],
                optionsApresentacao: [],
            }
        },
        methods: {
            checkAllowed() {
                let url = config.api +'/v1/admin/authorization/check?id_programa=672&id_user=<?php echo $_SESSION["admin"]?>';
                this.isLoading = true;
                Vue.http.get(url).then(res => {
                    this.allowed = res.body.allowed;
                    this.isLoading = false;
                    if (this.allowed) {
                        this.populateBases();
                    }
                }, err => {
                    this.isLoading = false;
                    console.log(2,err)
                });
            },
            save() {
                config.setapikey();
                let url = config.api + `/v1/admin/tolegacy/event/offer/save`;
                this.loading = true;
                let obj = {
                    id_apresentacao: this.form.id_apresentacao,
                    onOffer: this.form.onOffer,
                    onOfferOlderValue: this.form.onOfferOlderValue,
                    onOfferPercentage: this.form.onOfferPercentage,
                    onOfferText: this.form.onOfferText == undefined ? '' : this.form.onOfferText,
                };

                Vue.http.post(url, obj, { emulateJSON: true }).then(res => {
                    this.loading = false;
                    if (res.body.success) {
                        alert("Salvo com sucesso.");
                        this.getOffer();
                    }
                    else {
                        alert("Ocorreu uma falha ao tentar salvar.");
                    }
                }, err => {
                    this.loading = false;
                    console.log(2,err)
                });
            },
            onEditorBlur(quill) {
                console.log('editor blur!', quill)
            },
            onEditorFocus(quill) {
                console.log('editor focus!', quill)
            },
            onEditorReady(quill) {
                console.log('editor ready!', quill)
            },
            changeEvent() {
                this.getEvent();
            },
            getOffer() {
                Vue.nextTick().then(response => {
                    config.setapikey();
                    let url = config.api + `/v1/admin/tolegacy/event/offer/get?&id_apresentacao=${this.form.id_apresentacao}`;
                    this.loading = true;
                    Vue.http.get(url).then(res => {
                        this.form.loaded = true;
                        this.loading = false;
                        this.form.onOffer = res.body.onOffer;
                        this.form.onOfferOlderValue = res.body.onOfferOlderValue == undefined ? '' : res.body.onOfferOlderValue;
                        this.form.onOfferPercentage = res.body.onOfferPercentage == undefined ? '' : res.body.onOfferPercentage;
                        this.form.onOfferText = res.body.onOfferText == undefined ? '' : res.body.onOfferText;
                    }, err => {
                        this.loading = false;
                        console.log(2,err)
                    });
                });
            },
            populatePresentation() {
                Vue.nextTick().then(response => {
                    config.setapikey();
                    let url = config.api + `/v1/admin/tolegacy/event/presentation/list?&id_evento=${this.form.id_evento}`;
                    this.loading = true;
                    Vue.http.get(url).then(res => {
                        this.loading = false;
                        this.optionsApresentacao = res.body;
                    }, err => {
                        this.loading = false;
                        console.log(2,err)
                    });
                });
            },
            populateEvents() {
                Vue.nextTick().then(response => {
                    config.setapikey();
                    let url = config.api + `/v1/admin/tolegacy/event/list?&id_base=${this.form.id_base}`;
                    this.loading = true;
                    Vue.http.get(url).then(res => {
                        this.loading = false;
                        this.optionsEvents = res.body;
                    }, err => {
                        this.loading = false;
                        console.log(2,err)
                    });
                });
            },
            populateBases() {
                config.setapikey();
                let url = config.api + `/v1/admin/tolegacy/bases/list`;
                this.loading = true;
                Vue.http.get(url).then(res => {
                    this.loading = false;
                    this.optionsBases = res.body;
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
    .labelinput {
        min-width: 156px;
    }
    .ui-button-text {
        color: #fff !important;
    }
</style>
<link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.2.0/css/solid.css" integrity="sha384-wnAC7ln+XN0UKdcPvJvtqIH3jOjs9pnKnq9qX68ImXvOGz2JuFoEiCjT8jyZQX2z" crossorigin="anonymous">
<link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.2.0/css/fontawesome.css" integrity="sha384-HbmWTHay9psM8qyzEKPc8odH4DsOuzdejtnr+OFtDmOcIVnhgReQ4GZBH7uwcjf6" crossorigin="anonymous">

<?php require_once($_SERVER['DOCUMENT_ROOT'].'/admin/footer.php'); ?>