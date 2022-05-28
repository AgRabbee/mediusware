<template>
    <section>
        <div class="row" v-if="alertMsg!=''">
            <div class="col-md-12 ">
                <div class="alert" :class="alertType" role="alert">
                    {{ alertMsg }}
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <div class="card shadow mb-4">
                    <div class="card-body">
                        <div class="form-group">
                            <label for="">Product Name <span class="text-danger">*</span></label>
                            <input type="text" v-model="product_name" placeholder="Product Name" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="">Product SKU <span class="text-danger">*</span></label>
                            <input type="text" v-model="product_sku" placeholder="Product Name" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="">Description <span class="text-danger">*</span></label>
                            <textarea v-model="description" id="" cols="30" rows="4" class="form-control"></textarea>
                        </div>
                    </div>
                </div>

                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold text-primary">Media</h6>
                    </div>
                    <div class="card-body border">
                        <vue-dropzone ref="myVueDropzone" id="dropzone"
                                      @vdropzone-complete="imageUpload"
                                      :options="dropzoneOptions"></vue-dropzone>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold text-primary">Variants</h6>
                    </div>
                    <div class="card-body">
                        <div class="row" v-for="(item,index) in product_variant">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="">Option <span class="text-danger">*</span></label>
                                    <select v-model="item.option" class="form-control">
                                        <option v-for="variant in variants"
                                                :value="variant.id">
                                            {{ variant.title }}
                                        </option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label v-if="product_variant.length != 1" @click="product_variant.splice(index,1); checkVariant"
                                           class="float-right text-primary"
                                           style="cursor: pointer;">Remove</label>
                                    <label v-else for="">.</label>
                                    <input-tag v-model="item.tags" @input="checkVariant" class="form-control"></input-tag>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer" v-if="product_variant.length < variants.length && product_variant.length < 3">
                        <button @click="newVariant" class="btn btn-primary">Add another option</button>
                    </div>

                    <div class="card-header text-uppercase">Preview</div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                <tr>
                                    <td>Variant</td>
                                    <td>Price <span class="text-danger">*</span></td>
                                    <td>Stock <span class="text-danger">*</span></td>
                                </tr>
                                </thead>
                                <tbody>
                                <tr v-for="variant_price in product_variant_prices">
                                    <td>{{ variant_price.title }}</td>
                                    <td>
                                        <input type="text" class="form-control" v-model="variant_price.price">
                                    </td>
                                    <td>
                                        <input type="text" class="form-control" v-model="variant_price.stock">
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <button @click="saveProduct" type="submit" class="btn btn-lg btn-primary">Save</button>
        <button type="button" class="btn btn-secondary btn-lg">Cancel</button>
    </section>
</template>

<script>
import vue2Dropzone from 'vue2-dropzone'
import 'vue2-dropzone/dist/vue2Dropzone.min.css'
import InputTag from 'vue-input-tag'

export default {
    components: {
        vueDropzone: vue2Dropzone,
        InputTag
    },
    props: {
        variants: {
            type: Array,
            required: true
        },
        productdata:{
            type: Object,
        }
    },
    data() {
        return {
            product_id: '',
            product_name: '',
            product_sku: '',
            description: '',
            images: [],
            product_variant: [
                {
                    option: this.variants[0].id,
                    tags: []
                }
            ],
            product_variant_prices: [],
            dropzoneOptions: {
                url: 'http://localhost:8000/api/storeImage',
                thumbnailWidth: 150,
                maxFilesize: 0.5,
                headers: {"My-Awesome-Header": "header value"}
            },
            alertMsg:'',
            alertType:'',
            is_edit:false,
        }
    },
    methods: {
        // it will push a new object into product variant
        newVariant() {
            let all_variants = this.variants.map(el => el.id)
            let selected_variants = this.product_variant.map(el => el.option);
            let available_variants = all_variants.filter(entry1 => !selected_variants.some(entry2 => entry1 == entry2))
            // console.log(available_variants)

            this.product_variant.push({
                option: available_variants[0],
                tags: []
            })
        },

        // check the variant and render all the combination
        checkVariant() {
            let tags = [];
            this.product_variant_prices = [];
            this.product_variant.filter((item) => {
                tags.push(item.tags);
            })

            this.getCombn(tags).forEach(item => {
                this.product_variant_prices.push({
                    title: item,
                    price: 0,
                    stock: 0
                })
            })
        },

        // combination algorithm
        getCombn(arr, pre) {
            pre = pre || '';
            if (!arr.length) {
                return pre;
            }
            let self = this;
            let ans = arr[0].reduce(function (ans, value) {
                return ans.concat(self.getCombn(arr.slice(1), pre + value + '/'));
            }, []);
            return ans;
        },

        // store product into database
        saveProduct() {
            this.alertType = '';
            this.alertMsg = '';

            let product = {
                title: this.product_name,
                sku: this.product_sku,
                description: this.description,
                product_image: this.images,
                product_variant: this.product_variant,
                product_variant_prices: this.product_variant_prices
            }
            if(this.is_edit){
                product.product_id = this.product_id;
            }
            let flag;
            let result1;
            let result2;

            if(this.product_variant.length != 0){
                flag = true;
                result1 = this.product_variant.filter((ele)=>{
                    return ele.tags.length == 0;
                    // return (ele.tags == 0) || (ele.stock == 0);
                });
                flag = !(result1.length > 0);
            }

            if(this.product_variant_prices.length != 0){
                flag = true;
                result2 = this.product_variant_prices.filter((ele)=>{
                    return (ele.price == 0) || (ele.stock == 0);
                });
                flag = !(result2.length > 0);
            }

            if(this.product_name.length == 0 || this.product_sku.length == 0 || this.description.length == 0 || !flag ){
                alert("Please fill up all required information");
                return false;
            }
            axios.post('/product', product).then(response => {
                if(response.data.responseCode==1){

                    // clear fields data starts
                    this.product_id = '';
                    this.product_name = '';
                    this.product_sku = '';
                    this.description = '';
                    this.images = [];
                    this.product_variant = [
                        {
                            option: this.variants[0].id,
                            tags: []
                        }
                    ];
                    this.product_variant_prices = [];
                    // clear fields data ends

                    this.alertType = 'alert-success';
                    this.alertMsg = response.data.msg;
                }
            }).catch(error => {
                // console.log(error);
                this.alertType = 'alert-danger';
                this.alertMsg = response.data.msg;
            })
        },

        // image upload to directory
        imageUpload: async function (response){
            if(response.status == 'success'){
                this.images.push(JSON.parse(response.xhr.response).path)
            }else{
                console.log('image cannot upload');
            }
        },

        //manage edit data
        prepareDataForEdit(data){
            this.is_edit = true;
            this.product_id = data.product.id;
            this.product_name = data.product.title;
            this.product_sku = data.product.sku;
            this.description = data.product.description;

            let arr = Object.keys(data.productVariants).map((k) => data.productVariants[k]);
            this.product_variant= [];
            arr.forEach((ele)=>{
                let obj =  {
                    option: ele.option,
                    tags: ele.tags
                }
                this.product_variant.push(obj);
            });
            this.product_variant_prices = data.productVariantPrices;
        }


    },
    mounted() {
        if(this.productdata){
            this.prepareDataForEdit(this.productdata);
        }
    }
}
</script>
