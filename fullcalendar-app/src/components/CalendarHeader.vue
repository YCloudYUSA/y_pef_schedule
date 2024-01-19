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
      return this.selectedCategories.includes(category);
    },
    emitCategoryChange(category, isChecked) {
      this.$emit('categoryChange', { category, isChecked });
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
