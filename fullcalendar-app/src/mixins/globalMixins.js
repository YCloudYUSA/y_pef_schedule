// mixins/globalMixins.js
export default {
  data() {
    return {
      activeModal: null
    };
  },
  methods: {
    handleEscClose(event) {
      if (event.key === "Escape" && this.activeModal) {
        this.closeModal();
      }
    },
    closeModal() {
      this.activeModal = null;
    },
  },
  mounted() {
    window.addEventListener('keydown', this.handleEscClose);
  },
  unmounted() {
    window.removeEventListener('keydown', this.handleEscClose);
  },
};
