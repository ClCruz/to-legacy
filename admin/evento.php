<?php
require_once($_SERVER['DOCUMENT_ROOT']."/settings/unique_ui.php");
require_once($_SERVER['DOCUMENT_ROOT'].'/admin/acessoLogado.php');
?>
<?php require_once($_SERVER['DOCUMENT_ROOT'].'/admin/header.php'); ?>

<script src="https://cdn.quilljs.com/1.3.4/quill.js"></script>
<script src="https://cdn.jsdelivr.net/npm/vue-quill-editor@3.0.4/dist/vue-quill-editor.js"></script>
<script src="https://unpkg.com/vue-picture-input"></script> 
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
                                    <select v-model="form.id_evento" class="form-control" v-on:change="changeEvent" style="width: 30%" name="id_evento" id="id_evento">
                                        <option selected disabled>Escolha</option>
                                        <option v-for="option in optionsEvents" v-bind:value="option.value">
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
                    Informações do Evento
                </div>
                <div style="padding-left: 25px; padding-top: 15px;">
                    <div class="row">
                        <div class="form-group">
                            <div class="input-group input-group-sm">
                            <div class="input-group-prepend">
                                    <span class="input-group-text" id="inputGroup-sizing">URL</span>
                                </div>
                                <div class="input-group-prepend">
                                    <span class="input-group-text" id="inputGroup-sizing">{{form.uri_fixed}}</span>
                                </div>
                                <div class="input-group-prepend">
                                    <span class="input-group-text" id="inputGroup-sizing">{{form.uri_changeable}}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="form-group">
                            <div class="input-group input-group-sm">
                                <div class="input-group-prepend">
                                    <span class="input-group-text labelinput" id="inputGroup-sizing">Endereço para o Maps</span>
                                </div>
                                <input class="form-control inputbig" type="text" v-model="form.addressForMaps" placeholder="Endereço" name="address" id="address" maxlength="100">
                                <div class="input-group-append">
                                    <button class="btn btn-outline-secondary" v-on:click="seeInMaps" type="button">Ver</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="form-group">
                            <div class="input-group input-group-sm">
                                <div class="input-group-prepend">
                                    <span class="input-group-text" id="inputGroup-sizing-sm">Gênero</span>
                                </div>
                                <select v-model="form.id_genre" class="form-control" disabled style="width: 30%" name="id_genre" id="id_genre">
                                    <option selected disabled>Escolha</option>
                                    <option v-for="option in optionsGenre" v-bind:value="option.value">
                                        {{ option.text }}
                                    </option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="form-group">
                            <div class="input-group input-group-sm">
                                <div class="input-group-prepend">
                                    <span class="input-group-text labelinput" id="inputGroup-sizing">tag Meta-Description</span>
                                </div>
                                <input class="form-control inputbig" type="text" v-model="form.meta_description" placeholder="Descrição para a tag Meta Description" name="meta_description" id="meta_description" maxlength="300">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="form-group">
                            <div class="input-group input-group-sm">
                                <div class="input-group-prepend">
                                    <span class="input-group-text labelinput" id="inputGroup-sizing">tag Meta-Keyword</span>
                                </div>
                                <input class="form-control inputbig" type="text" v-model="form.meta_keyword" placeholder="Descrição para a tag Meta Keyword" name="meta_keyword" id="meta_keyword" maxlength="160">
                            </div>
                        </div>
                    </div>
                    <div class="row" v-if="form.hasImage">
                        <div id="inputGroup-sizing-sm" style="padding-bottom: 10px;">Imagem do Evento</div>
                    </div>
                    <div class="row" v-if="form.hasImage">
                        <img v-if="!form.changedImage" v-on:click="imageClick" width="160" height="180" :src="form.imageURI" alt="" title="Clique em cima para trocar a imagem." style="cursor: pointer;" />
                    </div>
                    <div class="row" v-if="!form.hasImage" style="padding-bottom: 10px;">
                        <div id="inputGroup-sizing-sm">Imagem do Evento</div>
                    </div>
                    <div class="row" v-if="!form.hasImage">
                            <picture-input
                            ref="pictureInput" 
                            @change="onChange" 
                            width="160" 
                            height="180" 
                            margin="0"
                            accept="image/jpeg,image/png" 
                            :crop="false"
                            :hide-change-button="true"
                            size="1" 
                            button-class="btn"
                            :custom-strings="picOptions"
                            ></picture-input>
                    </div>
                    <div class="row">
                        <span id="inputGroup-sizing-sm" style="padding-bottom: 10px; padding-top:20px;">Descrição</span>
                    </div>
                    <div class="row">
                        <quill-editor v-model="form.content"
                            ref="editor"
                            :options="quillOptions"
                            @blur="onEditorBlur($event)"
                            @focus="onEditorFocus($event)"
                            @ready="onEditorReady($event)">
                        </quill-editor>

                    </div>
                    <div class="row" style="padding-top: 10px;">
                        <div class="form-group">
                            <div class="input-group input-group-sm mb-3 col-6">
                                <button v-on:click="save" type="button" class="btn btn-sm">
                                        Salvar
                                </button>
                            </div>
                        </div>
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

    Vue.use(VueQuillEditor);

    //Vue.component('modal', {
    //    template: '#modal-template'
    //});


    new Vue({
        el: '#app',
        components: {
            Loading: VueLoading,
            LocalQuillEditor: VueQuillEditor.quillEditor,
            'picture-input': PictureInput,
        },
        mounted() {
            this.checkAllowed();
        },
        computed: {
        
        },
        data () {
            return {
                allowed: false,
                loading: false,
                executing: false,
                picOptions: {
                    upload: '<p>Não foi possível realizar o upload.</p>', // HTML allowed
                    drag: 'Arraste a imagem ou clique para selecionar', // HTML allowed
                    tap: 'Toque aqui para selecionar uma imagem', // HTML allowed
                    change: 'Mudar', // Text only
                    remove: 'Remover', // Text only
                    select: 'Selecione uma imagem', // Text only
                    selected: '<p>Imagem selecionada com sucesso.</p>', // HTML allowed
                    fileSize: 'O tamanho da imagem ultrapassou o limite.', // Text only
                    fileType: 'Esse tipo de arquivo não é suportado.', // Text only
                    aspect: 'Landscape/Portrait' // Text only
                },
                quillOptions: {
                    modules: {
                    toolbar: [ [{ header: [1, 2, false] }],
                               ,['bold', 'italic', 'underline']
                               ,[{ 'color': [] }, { 'background': [] }]
                               ,[{ 'align': [] }]
                               ,['clean']
                             ]
                    },
                    placeholder: 'Descrição do evento',
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
                    content: "",
                    changedImage: false,
                    uri: "",
                    uri_fixed: "",
                    uri_changeable: "",
                    image: "",
                    imageURI: "",
                    imageBase64: "",
                    ticketsPerPurchase: 12,
                    minuteBefore: 1440,
                    addressForMaps: "",
                    meta_description: "",
                    meta_keyword: "",
                    id_genre: 0,
                },
                optionsBases : [],
                optionsEvents : [],
                optionsGenre: [],
            }
        },
        methods: {
            checkAllowed() {
                let url = config.api +'/v1/admin/authorization/check?id_programa=663&id_user=<?php echo $_SESSION["admin"]?>';
                this.isLoading = true;
                Vue.http.get(url).then(res => {
                    this.allowed = res.body.allowed;
                    this.isLoading = false;
                    if (this.allowed) {
                        this.populateBases();
                        this.populateGenre();
                    }
                }, err => {
                    this.isLoading = false;
                    console.log(2,err)
                });
            },
            save() {
                config.setapikey();
                let url = config.api + `/v1/admin/event/save`;
                this.loading = true;
                let obj = {
                    id: this.form.id_evento,
                    description: this.form.content,
                    //uri: this.form.uri_changeable,
                    address: this.form.addressForMaps,
                    imageChanged: this.form.changedImage,
                    base64: this.form.image,
                    meta_description: this.form.meta_description,
                    meta_keyword: this.form.meta_keyword,
                    id_genre: this.form.id_genre
                };

                Vue.http.post(url, obj, { emulateJSON: true }).then(res => {
                    this.loading = false;
                    if (res.body.success) {
                        alert("Salvo com sucesso.");
                        this.getEvent();
                    }
                    else {
                        if (res.body.msg == "URINOTUNIQUE") {
                            alert("Verifique a URL, já existe um outro evento com a mesma URL.");
                        }
                        else {
                            alert("Ocorreu uma falha ao tentar salvar.");
                        }
                    }
                }, err => {
                    this.loading = false;
                    console.log(2,err)
                });
            },
            imageClick() {
                this.form.hasImage = false;

            },
            onChange (image) {
                if (image) {
                    this.form.image = image;
                    this.form.changedImage = true;
                }
            },
            seeInMaps() {
                window.open("http://maps.google.com/?q="+this.form.addressForMaps);
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
            getEvent() {
                Vue.nextTick().then(response => {
                    config.setapikey();
                    let url = config.api + `/v1/admin/event/get?&id_evento=${this.form.id_evento}`;
                    this.loading = true;
                    Vue.http.get(url).then(res => {
                        this.form.loaded = true;
                        this.loading = false;
                        this.form.content = res.body.description;
                        this.form.uri = res.body.uri;
                        this.form.uri_fixed = "/evento/";

                        if (this.form.uri!="") {
                            let splited = this.form.uri.split("/");
                            if (splited.length >=3)
                            {
                                this.form.uri_fixed = "/" + splited[1] + "/";
                                this.form.uri_changeable = splited[2];
                            } else if (splited.length >=2)
                            {
                                this.form.uri_fixed =  "/" + splited[0] + "/";
                                this.form.uri_changeable = splited[1];
                            }
                        }
                        this.form.imageURI = res.body.imageURI + "?" + new Date().getTime();
                        this.form.imageBase64 = res.body.imageBase64;
                        this.form.ticketsPerPurchase = res.body.ticketsPerPurchase;
                        this.form.minuteBefore = res.body.minuteBefore;
                        this.form.addressForMaps = res.body.address;
                        this.form.meta_description = res.body.meta_description;
                        this.form.meta_keyword = res.body.meta_keyword;
                        this.form.id_genre = res.body.id_genre;
                    }, err => {
                        this.loading = false;
                        console.log(2,err)
                    });
                });
            },
            populateGenre() {
                Vue.nextTick().then(response => {
                    config.setapikey();
                    let url = config.api + `/v1/admin/genre/list`;
                    this.loading = true;
                    Vue.http.get(url).then(res => {
                        this.loading = false;
                        this.optionsGenre = res.body;
                    }, err => {
                        this.loading = false;
                        console.log(2,err)
                    });
                });
            },
            populateEvents() {
                Vue.nextTick().then(response => {
                    config.setapikey();
                    let url = config.api + `/v1/admin/event/list?&id_base=${this.form.id_base}`;
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
                let url = config.api + `/v1/admin/bases/list`;
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
</style>
<link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.2.0/css/solid.css" integrity="sha384-wnAC7ln+XN0UKdcPvJvtqIH3jOjs9pnKnq9qX68ImXvOGz2JuFoEiCjT8jyZQX2z" crossorigin="anonymous">
<link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.2.0/css/fontawesome.css" integrity="sha384-HbmWTHay9psM8qyzEKPc8odH4DsOuzdejtnr+OFtDmOcIVnhgReQ4GZBH7uwcjf6" crossorigin="anonymous">

<?php require_once($_SERVER['DOCUMENT_ROOT'].'/admin/footer.php'); ?>