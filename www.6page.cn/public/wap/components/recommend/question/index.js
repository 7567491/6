define([
    'swiper',
    'text!components/recommend/question/index.html',
    'css!components/recommend/question/index.css'
], function(Swiper, html) {
    return {
        props: {
            obj: {
                type: Object,
                default: function () {
                    return {};
                }
            }
        },
        data: function () {
            return {
                swiperOptions: {
                    slidesPerView: 'auto',
                    spaceBetween: 10
                }
            };
        },
        mounted: function () {
            this.$nextTick(function () {
                this.swiper = new Swiper('#swiper6', this.swiperOptions);
            });
        },
        template: html
    };
});