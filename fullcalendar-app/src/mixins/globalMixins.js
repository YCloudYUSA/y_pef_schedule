// mixins/globalMixins.js

// This mixin provides functionality for managing modals across the application.
export default {
  data() {
    return {
      // activeModal holds the name of the currently active modal, if any.
      activeModal: null
    };
  },
  methods: {
    // handleEscClose is an event handler that closes the active modal when the Escape key is pressed.
    handleEscClose(event) {
      if (event.key === "Escape" && this.activeModal) {
        this.closeModal();
      }
    },
    // closeModal sets the active modal to null, effectively closing it.
    closeModal() {
      this.activeModal = null;
    },
  },
  // When the component is mounted, it starts listening for the 'keydown' event globally.
  mounted() {
    window.addEventListener('keydown', this.handleEscClose);
  },
  // Before the component is destroyed, it removes the 'keydown' event listener to prevent memory leaks.
  unmounted() {
    window.removeEventListener('keydown', this.handleEscClose);
  },
};
