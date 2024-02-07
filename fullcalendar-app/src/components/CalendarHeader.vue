<template>
  <div class="fullcalendar--header">
    <div class="pdf">
      <button @click="$emit('downloadPDF')">Download PDF</button>
    </div>
    <div class="checkbox-container">
      <div v-for="category in categories" :key="category" class="checkbox-item">
        <input
          type="checkbox"
          :value="category"
          :checked="isSelected(category)"
          @change="emitCategoryChange(category, $event.target.checked)"
        />
        <label>{{ category }}</label>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'CalendarHeader',
  props: {
    categories: Array,
    selectedCategories: Array
  },
  methods: {
    isSelected(category) {
      // Make sure selectedCategories is an array before calling includes
      if (Array.isArray(this.selectedCategories)) {
        return this.selectedCategories.includes(category);
      }
      console.error('selectedCategories is not an array:', this.selectedCategories);
      return false;
    },
    emitCategoryChange(category, isChecked) {
      // Create a new array that reflects the change
      let updatedCategories = isChecked
        // Add the category
        ? [...this.selectedCategories, category]
        // Remove the category
        : this.selectedCategories.filter(cat => cat !== category);

      // Make sure there are no duplicates
      updatedCategories = Array.from(new Set(updatedCategories));

      // Emit the updated array of selected categories
      this.$emit('categoryChange', updatedCategories);
    }
  }
};
</script>

<style>
.fullcalendar--header {
  display: flex;
  justify-content: space-between;
  flex-wrap: wrap;
  padding: 30px 0;
}
.checkbox-container {
  display: flex;
  flex-wrap: wrap;
}
.checkbox-item {
  margin-right: 15px;
}
.checkbox-item input[type="checkbox"]:checked {
  color: green;
}
</style>
