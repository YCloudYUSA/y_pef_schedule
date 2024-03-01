<template>
  <div class="calendar-branch-header">
    <div class="calendar-branch-info" v-if="branch">
      <h4>{{ 'Branch name' }}</h4>
      <h2>{{ branch }}</h2>
    </div>
  </div>

  <div class="fullcalendar--header">
    <div class="pdf">
      <button class="download-pdf-button" @click="$emit('downloadPDF')">
        <i class="bi bi-download"></i>
        Download PDF
      </button>
    </div>

    <button class="legend-toggle-button" @click="toggleLegend">Legend +</button>
    <div :class="['checkbox-container', { active: isLegendOpen }]">
      <label class="custom-checkbox" v-for="category in categories" :key="category.name">
        <input
          type="checkbox"
          :value="category.name"
          :checked="isSelected(category.name)"
          @change="emitCategoryChange(category.name, $event.target.checked)"
          class="custom-checkbox-input"
        />
        <span class="checkmark" :style="{ 'background-color': category.color, 'border-color': category.color }"></span>
        {{ category.name }}
      </label>
    </div>
  </div>
</template>

<script>
import EventService from '../service/EventService';

export default {
  name: 'CalendarHeader',
  props: {
    categories: Array,
    selectedCategories: Array
  },
  data() {
    return {
      isLegendOpen: false,
      branch: null,
    };
  },
  mounted() {
    const eventService = new EventService();
    this.branch = eventService.getBranch();
  },
  methods: {
    toggleLegend() {
      this.isLegendOpen = !this.isLegendOpen;
    },
    isSelected(categoryName) {
      // Make sure selectedCategories is an array before calling includes
      if (Array.isArray(this.selectedCategories)) {
        return this.selectedCategories.includes(categoryName);
      }
      console.error('selectedCategories is not an array:', this.selectedCategories);
      return false;
    },
    emitCategoryChange(categoryName, isChecked) {
      // Create a new array that reflects the change
      let updatedCategories = isChecked
        // Add the category
        ? [...this.selectedCategories, categoryName]
        // Remove the category
        : this.selectedCategories.filter(cat => cat !== categoryName);

      // Make sure there are no duplicates
      updatedCategories = Array.from(new Set(updatedCategories));

      // Emit the updated array of selected categories
      this.$emit('categoryChange', updatedCategories);
    }
  }
};
</script>
<style scoped>
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
.custom-checkbox {
  display: flex;
  align-items: center;
  position: relative;
  padding-left: 20px;
  cursor: pointer;
  font-size: 14px;
  user-select: none;
  margin-right: 15px;
}

.custom-checkbox input {
  position: absolute;
  opacity: 0;
  cursor: pointer;
  height: 0;
  width: 0;
}

.checkmark {
  position: absolute;
  left: 0;
  height: 17px;
  width: 17px;
  background-color: #eee;
  border-radius: 4px;
  border: 1px solid #ddd;
}

.custom-checkbox:hover input ~ .checkmark {
  background-color: #ccc;
}

.custom-checkbox input:checked ~ .checkmark {
  background-color: #2196F3;
  border: 1px solid #2196F3;
}

.checkmark:after {
  content: "";
  position: absolute;
  display: none;
}

.custom-checkbox input:checked ~ .checkmark:after {
  display: block;
}

.custom-checkbox .checkmark:after {
  content: "";
  position: absolute;
  display: none;
  left: 50%;
  top: 50%;
  width: 5px;
  height: 10px;
  border: solid white;
  border-width: 0 2px 2px 0;
  transform: translate(-50%, -50%) rotate(45deg);
}

/* MOBILE DEVICES */
@media (max-width: 768px) {
  .legend-toggle-button {
    display: block;
  }
  .checkbox-container {
    display: none;
  }
  .checkbox-container.active {
    display: flex;
  }
}

/* DESKTOP */
@media (min-width: 768px) {
  .legend-toggle-button {
    display: none;
  }
  .checkbox-container {
    display: flex;
  }
}

.calendar-branch-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 10px 0;
}

.calendar-branch-header .calendar-branch-info {
  text-align: left;
}

.calendar-branch-header .calendar-branch-info h4 {
  margin: 0;
  font-size: 14px;
  font-weight: 600;
  color: #000;
  text-transform: uppercase;
  letter-spacing: 1px;
}

.calendar-branch-header .calendar-branch-info h2 {
  margin: 0;
  font-weight: bold;
  color: #000;
}
</style>
