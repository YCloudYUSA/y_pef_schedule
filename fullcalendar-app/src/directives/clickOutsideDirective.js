export default {
  beforeMount(el, binding) {
    el.clickOutsideEvent = function(event) {
      // We check whether the click was on the calendar element
      const calendarEventClicked = event.target.closest('.fc-timegrid-event-harness-inset');

      // If the click is outside the element, the method is passed and the
      // click is not on the calendar event.
      if (!(el.contains(event.target)) && binding.value && !calendarEventClicked) {
        binding.value(event, el);
      }
    };
  },
  unmounted(el) {
    document.removeEventListener('click', el.clickOutsideEvent);
  },
};
