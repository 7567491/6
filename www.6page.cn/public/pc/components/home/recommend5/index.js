define([
    'mixins/router',
    'text!components/home/recommend5/index.html',
    'swiper'
], function(routerMixin, html, Swiper) {
    return {
        mixins: [routerMixin],
        props: {
            recommend: {
                type: Object,
                default: function () {
                    return {};
                }
            },
            rankList: {
                type: Array,
                default: function () {
                    return [];
                }
            },
            goodList: {
                type: Array,
                default: function () {
                    return [];
                }
            },
            newList: {
                type: Array,
                default: function () {
                    return [];
                }
            }
        },
        watch: {
            goodList: function () {
                var vm = this;
                this.$nextTick(function () {
                    this.swiper = new Swiper('.swiper-container', {
                        slidesPerView: 'auto',
                        spaceBetween: 10,
                        centeredSlides: true,
                        initialSlide: 1,
                        autoplay: true,
                        loop: true,
                        // loopedSlides: 4,
                        observer: true,
                        observeParents: true,
                        observeSlideChildren: true
                    });
                });
            }
        },
        mounted: function () {
            
        },
        methods: {

        },
        template: html
    };
});