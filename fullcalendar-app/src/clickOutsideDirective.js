export default {
  beforeMount(el, binding) {
    el.clickOutsideEvent = function(event) {
      // Перевіряємо, чи клік був на елементі календаря
      const calendarEventClicked = event.target.closest('.fc-timegrid-event-harness-inset');

      // Якщо клік поза елементом, метод переданий, і клік не на календарному івенті
      if (!(el.contains(event.target)) && binding.value && !calendarEventClicked) {
        console.log("click outside event triggered");

        binding.value(event, el);
      }
    };
  },
  unmounted(el) {
    document.removeEventListener('click', el.clickOutsideEvent);
  },
};
