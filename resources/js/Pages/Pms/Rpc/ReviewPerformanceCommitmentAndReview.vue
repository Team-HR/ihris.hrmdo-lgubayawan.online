<style scoped>
table,
th,
td {
  font-size: 14px;
  padding: 5px;
  border: 0.5px solid rgb(185, 185, 185);
  border-collapse: collapse;
}
</style>

<template>
  <auth-layout>
    <Card class="w-full">
      <template #title>
        <Button label="Back" class="p-button-sm p-button-raised p-button-text mb-3" icon="bi bi-arrow-left"
                @click="go_back()"></Button>
        <br />
        <span><i class="bi bi-book mr-2"></i> Review Performance Commitment and Review</span></template>
      <template #subtitle>
        <span class="text-xl">{{ $page.props.auth.user.sys_department_name }} ( {{ period.period }},
          {{ period.year }})</span>
        <br />
        Review personnel's accomplished performance commitment and review.</template>
      <template #content>
        <table class="w-full">
          <tr>
            <th class="text-xl">Name</th>
            <th class="text-xl">Form Type</th>
            <th class="text-xl">Status</th>
          </tr>
          <tr v-if="items.length < 1">
            <td colspan="3" class="text-xl p-5 text-center bg-gray-300">
              No personnel under direct supervision.
            </td>
          </tr>
          <tr v-for="(item, i) in items" :key="i">
            <!-- <td>{{ item.label }}</td>
            <td class="" v-html="item.status"></td> -->
            <td colspan="3"><Button :disabled="!item.is_submitted" label="Open" @click="$inertia.get(`/pms/rpc/${item.id}/form`)"></Button> {{ item }}
            </td>
          </tr>
        </table>
      </template>
    </Card>
  </auth-layout>
</template>
<script>
import AuthLayout from "@/Layouts/Authenticated";

export default {
  props: {
    period: null,
    items: {
      type: Array,
      default: []
    }
  },
  components: {
    AuthLayout,
  },
  data() {
    return {
      current_url: document.location.pathname,
    };
  },
  methods: {
    go_back() {
      window.history.back();
    },

  },
  created() { },
  mounted() { },
};
</script>
