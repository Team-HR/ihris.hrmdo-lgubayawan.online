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
        <span><i class="bi bi-book mr-2"></i> PERFORMANCE COMMITMENT AND REVIEW</span></template>
      <template #subtitle>
        <span class="text-xl">{{ $page.props.auth.user.sys_department_name }} ( {{ period.period }},
          {{ period.year }})</span>
        <br />
        Accomplish/Review your Performance Commitments</template>
      <template #content>
        <table class="">
          <!-- <thead>
            <tr>
              <th>Option</th>
              <th>Form</th>
              <th>Status</th>
            </tr>
          </thead> -->
          <tr v-for="(item, i) in items" :key="i">
            <td v-if="!form_status.is_submitted">
              <!--  -->
              <Button @click="$inertia.get(item.href, {}, { replace: true })"
                      :label="item.b_label ? item.b_label : 'Edit'" class="p-button-sm"
                      :disabled="item.is_disabled"></Button>
              <!-- submit button start-->
              <!-- <Button v-else @click="submit(item)" :label="item.b_label ? item.b_label : 'Submit'" class="p-button-sm"
                      :disabled="item.is_disabled"></Button> -->
              <!-- submit button end-->
            </td>
            <td>{{ item.label }}</td>
            <td class="" v-html="item.status"></td>
          </tr>

          <tr>
            <td v-if="!form_status.is_submitted">
              <!-- {{ form_status }} -- {{ form_status.is_submitted }} -->
              <Button label="Submit" class="p-button-sm" @click="submit" :disabled="checkIfSubmittable"></Button>
            </td>
            <td>{{ !form_status.is_submitted ? "Submit & Finalize" : "Submitted" }}</td>
            <td v-html="submit_status_text"></td>
          </tr>


        </table>

        <Button label="Print Form" icon="bi bi-print" v-if="form_status.is_submitted" class="mt-2"
                @click="printForm()"></Button>

      </template>
    </Card>
    <Toast />
  </auth-layout>
</template>
<script>
import AuthLayout from "@/Layouts/Authenticated";
import { router } from '@inertiajs/vue3';
import { useForm } from '@inertiajs/vue3'


export default {
  props: {
    period: null,
    form_status: null,
  },
  components: {
    AuthLayout,
  },
  data() {
    return {
      current_url: document.location.pathname,
      items: [],
      form: useForm(this.form_status)
    };
  },
  created() {
    var items = [
      {
        no: 1,
        href: this.current_url + "/form_type/" + this.form_status.id,
        label: "Form Type",
        status: (() => {
          var agency = this.form_status.agency
            ? this.form_status.agency.toUpperCase()
            : "________";
          var form_type = this.form_status.form_type
            ? this.form_status.form_type.toUpperCase()
            : "________";
          return this.form_status.agency
            ? `Agency: <b class="text-green-700_ mr-3"> ${agency} </b>
               Type: <b class="text-green-700_ mr-3"> ${form_type}</b>`
            : "Please set Form Type!";
        })(),
      },
      {
        no: 2,
        href: this.current_url + "/signatories/" + this.form_status.id,
        label: "Signatories",
        status: (() => {
          if (!this.form_status.agency) return "Set Form Type first!";
          // return this.form_status.signatories_inputs;
          var immediate_supervisor,
            department_head,
            head_of_agency = "";
          if (this.form_status.agency == "lgu") {
            immediate_supervisor = this.form_status.signatories_inputs
              .immediate_supervisor
              ? this.form_status.signatories_inputs.immediate_supervisor.full_name
              : "_________";
            department_head = this.form_status.signatories_inputs.department_head
              ? this.form_status.signatories_inputs.department_head.full_name
              : "_________";
          } else {
            immediate_supervisor = this.form_status.signatories_inputs
              .immediate_supervisor
              ? this.form_status.signatories_inputs.immediate_supervisor
              : "_________";
            department_head = this.form_status.signatories_inputs.department_head
              ? this.form_status.signatories_inputs.department_head
              : "_________";
          }
          head_of_agency = this.form_status.signatories_inputs.head_of_agency
            ? this.form_status.signatories_inputs.head_of_agency
            : "_________";
          return `Immediate Supervisor: <b class="text-green-700_ mr-3"> ${immediate_supervisor}</b>
                    Department Head:<b class="text-green-700_ mr-3"> ${department_head}</b> Head of Agency: <b class="text-green-700_">${head_of_agency}</b>`;
        })(),
        is_disabled: !this.form_status.agency ? true : false,
      },
      {
        no: 3,
        href: this.current_url + "/core_functions/" + this.form_status.id,
        label: "Core Functions",
        status: (() => {
          if (!this.form_status.agency) return "Set Form Type first!";
          var total_percentage_weight = this.form_status.total_percentage_weight
            ? this.form_status.total_percentage_weight
            : "_________";
          var total_average_rating = this.form_status.total_average_rating
            ? this.form_status.total_average_rating
            : "_________";
          return this.status_text(total_percentage_weight, total_average_rating);
        })(),
        is_disabled: !this.form_status.agency ? true : false,
      },
      {
        no: 4,
        href: this.current_url + "/strategic_function/" + this.form_status.id,
        label: "Strategic Function",
        status: (() => {
          if (!this.form_status.agency) return "Set Form Type first!";
          var total_percentage_weight = this.form_status.strat_total_percentage_weight
            ? this.form_status.strat_total_percentage_weight
            : "_________";
          var total_average_rating = this.form_status.strat_total_average_rating
            ? this.form_status.strat_total_average_rating
            : "_________";
          return this.status_text(total_percentage_weight, total_average_rating);
        })(),
        is_disabled: !this.form_status.agency ? true : false,
      },
      {
        no: 5,
        href: this.current_url + "/support_functions",
        label: "Support Function",
        status: (() => {
          if (!this.form_status.agency) return "Set Form Type first!";
          var total_percentage_weight = this.form_status.support_total_percentage_weight
            ? this.form_status.support_total_percentage_weight
            : "_________";
          var total_average_rating = this.form_status.support_total_average_rating && this.form_status.support_total_average_rating != "0.00"
            ? this.form_status.support_total_average_rating
            : "_________";
          return this.status_text(total_percentage_weight, total_average_rating);
        })(),
        is_disabled: !this.form_status.agency ? true : false,
      },
      // {
      //   no: 6,
      //   href: "/api/" + this.current_url + "/submit",
      //   b_label: "Submit",
      //   label: this.form_status.is_submitted ? "Submitted" : "Finalize & Submit",
      //   status: (() => {
      //     if (!this.form_status.agency) return "Set Form Type first!";
      //     var total_percentage_weight = this.form_status.overall_percentage_weight
      //       ? this.form_status.overall_percentage_weight
      //       : "_________";
      //     var total_average_rating = this.form_status.overall_numerical_rating
      //       ? this.form_status.overall_numerical_rating
      //       : "_________";
      //     return `Total Percentage Weight (%): <b class="text-green-700_ mr-3">${total_percentage_weight}%</b>Total Numerical Rating: <b class="text-green-700_ mr-3">${total_average_rating}</b>`;
      //   })(),
      //   is_disabled: !this.form_status.agency ? true : false,
      // },
    ];

    this.items = items;
  },
  computed: {
    submit_status_text() {
      if (!this.form_status.agency) return "Set Form Type first!";
      var total_percentage_weight = this.form_status.overall_percentage_weight
        ? this.form_status.overall_percentage_weight
        : "_________";
      var total_average_rating = this.form_status.overall_numerical_rating
        ? this.form_status.overall_numerical_rating
        : "_________";
      return `Total Percentage Weight (%): <b class="text-green-700_ mr-3">${total_percentage_weight}%</b>Total Numerical Rating: <b class="text-green-700_ mr-3">${total_average_rating}</b>`;
    },

    checkIfSubmittable() {
      if (this.form_status.total_average_rating == '0.00' || this.form_status.strat_total_average_rating == '0.00' || this.form_status.support_total_average_rating == '0.00') {
        return true
      }
      // strat_total_average_rating
      // support_total_average_rating
      // total_average_rating
      // return true;
    }

  },

  methods: {
    printForm() {
      router.get(this.current_url + '/print/' + this.form_status.id);
    },
    go_back() {
      window.history.back();
    },

    status_text(total_percentage_weight, total_average_rating) {
      return `Percentage Weight (%): <b class="text-green-700_ mr-3">${total_percentage_weight}%</b>Rating: <b class="text-green-700_ mr-3">${total_average_rating}</b>`;
    },
    submit() {
      // console.log(this.form);
      this.form.post(this.current_url + "/submit", {
        onSuccess: (page) => {
          this.$toast.add({
            severity: "success",
            summary: "Submitted",
            detail: "PCR Successfully Submitted!",
            life: 3000,
          });
        },
      })
    },
    go_back() {
      window.history.back();
    },
  },

  mounted() {
    router.reload({ only: ["form_status"] });
    // console.log(this.form_status);
  },
};
</script>
