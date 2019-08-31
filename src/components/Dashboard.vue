<template>
    <div>
        <b-container>
            <b-row>
                <b-col cols="3">
                    <b-list-group>
                        <b-list-group-item v-for="(d,i) in results" :key="i" v-show="hasContent(d)">
                            <a href="#" @click="current = d">{{i}}</a>
                        </b-list-group-item>
                    </b-list-group>
                </b-col>
                <b-col cols="9">
                    <b-card header="Issues" v-if="current">
                        Total {{total}}

                        <div v-for="(record,id) in current" :key="id">
                            <b-row class="mb-3">
                                <b-col>
                                    <b-card >
                                        <table class="table table-sm" style="font-size:12px;">
                                            <tr v-for="(val,i2) in record.left" :key="i2 + Math.random()">
                                                <td :class="fieldClass(record.differences,i2)">{{i2}}</td>
                                                <td>{{val}}</td>
                                            </tr>
                                        </table>
                                    </b-card>
                                </b-col>
                                <b-col>
                                    <b-card>
                                        <table class="table table-sm" style="font-size:12px;">
                                            <tr v-for="(val,i2) in record.right" :key="i2 + Math.random()">
                                                <td :class="fieldClass(record.differences,i2)">{{i2}}</td>
                                                <td>{{val}}</td>
                                            </tr>
                                        </table>
                                    </b-card>
                                </b-col>
                            </b-row>
                        </div>
                    </b-card>
                </b-col>
            </b-row>
        </b-container>
    </div>
</template>

<script>
let _ = require("lodash")
export default{
    data(){
        return {
            results : [],
            current :null,
        }
    },
    async mounted(){
        let request = await this.$http.get("http://10.100.100.12/diffchecker/public/compare/test2.php");
        this.results = request.data;
    },
    computed : {
        total(){
            return _.size(this.current)
        }
    },
    methods : {
        fieldClass(differences,key){
            return _.has(differences,key) ? 'text-danger font-weight-bold' : ''
        },
        hasContent(d){
            return _.size(d)
        }
    }
}
</script>